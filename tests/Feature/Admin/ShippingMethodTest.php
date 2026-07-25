<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The admin UI for the Shipping sidebar item — ShippingMethod itself (fee,
 * structured delivery_time_min_days/max_days, a code-based fallback via
 * ShippingMethod::DEFAULT_CODE, ensureAtLeastOneActive() self-healing) was
 * already real and used by checkout before this; only the CRUD screen to
 * manage rows was missing. No is_default boolean exists in the schema —
 * the fallback concept is entirely "code === 'standard'", so these tests
 * check that, not an invented flag.
 */
class ShippingMethodTest extends TestCase
{
    use RefreshDatabase;

    protected function makeAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        return $admin;
    }

    protected function makeEmployee(): User
    {
        $employee = User::factory()->create();
        $employee->assignRole(Role::findOrCreate('employee', 'web'));

        return $employee;
    }

    protected function grant(User $user, string $permission): void
    {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    protected function makeMethod(array $overrides = []): ShippingMethod
    {
        return ShippingMethod::create(array_merge([
            'code' => 'method-'.uniqid(),
            'name_ar' => 'طريقة شحن', 'name_en' => 'Shipping Method',
            'fee' => 60, 'estimated_days' => '2-4',
            'delivery_time_min_days' => 2, 'delivery_time_max_days' => 4,
            'is_active' => true, 'sort_order' => 0,
        ], $overrides));
    }

    // ---------------------------------------------------------------
    // Access control
    // ---------------------------------------------------------------

    public function test_a_bare_employee_without_the_permission_is_forbidden(): void
    {
        $employee = $this->makeEmployee();

        $this->actingAs($employee)->get(route('admin.shipping-methods.index'))->assertForbidden();
    }

    public function test_an_employee_granted_shipping_settings_edit_can_view_it(): void
    {
        $employee = $this->makeEmployee();
        $this->grant($employee, 'shipping_settings.edit');
        $this->makeMethod(['name_ar' => 'Grantee Visible Method', 'name_en' => 'Grantee Visible Method']);

        $response = $this->actingAs($employee)->get(route('admin.shipping-methods.index'));

        $response->assertOk();
        $response->assertSee('Grantee Visible Method');
    }

    public function test_admin_can_view_it_regardless_of_seeded_permissions(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get(route('admin.shipping-methods.index'))->assertOk();
    }

    // ---------------------------------------------------------------
    // Listing
    // ---------------------------------------------------------------

    public function test_lists_name_fee_delivery_estimate_and_status(): void
    {
        $admin = $this->makeAdmin();
        $this->makeMethod([
            'name_ar' => 'Listed Method', 'name_en' => 'Listed Method', 'fee' => 90,
            'delivery_time_min_days' => 2, 'delivery_time_max_days' => 5,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.shipping-methods.index'));

        $response->assertOk();
        $response->assertSee('Listed Method');
        $response->assertSee('90');
        $response->assertSee('2–5');
        $response->assertSee(__('general.active'));
    }

    public function test_the_standard_code_method_shows_the_fallback_badge(): void
    {
        $admin = $this->makeAdmin();
        $this->makeMethod(['code' => ShippingMethod::DEFAULT_CODE, 'name_ar' => 'Fallback Method', 'name_en' => 'Fallback Method']);
        $this->makeMethod(['code' => 'express', 'name_ar' => 'Non Fallback Method', 'name_en' => 'Non Fallback Method']);

        $response = $this->actingAs($admin)->get(route('admin.shipping-methods.index'));

        $response->assertOk();
        $content = $response->getContent();
        $fallbackPos = strpos($content, 'Fallback Method');
        $nonFallbackPos = strpos($content, 'Non Fallback Method');
        $badgePos = strpos($content, __('shipping_methods.fallback_badge'));

        $this->assertNotFalse($badgePos);
        // The badge sits between the two row labels in document order —
        // proof it's attached to "Fallback Method"'s row, not just present
        // somewhere on the page.
        $this->assertGreaterThan($fallbackPos, $badgePos);
        $this->assertLessThan($nonFallbackPos, $badgePos);
    }

    // ---------------------------------------------------------------
    // Create / update
    // ---------------------------------------------------------------

    public function test_admin_can_create_a_shipping_method(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->post(route('admin.shipping-methods.store'), [
            'code' => 'same-day',
            'name_ar' => 'توصيل في نفس اليوم', 'name_en' => 'Same Day Delivery',
            'fee' => 200,
            'delivery_time_min_days' => 0,
            'delivery_time_max_days' => 1,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.shipping-methods.index'));
        $this->assertDatabaseHas('shipping_methods', [
            'code' => 'same-day', 'name_en' => 'Same Day Delivery', 'fee' => 200,
            'delivery_time_min_days' => 0, 'delivery_time_max_days' => 1,
        ]);
    }

    public function test_creating_derives_the_legacy_estimated_days_string_from_the_structured_range(): void
    {
        // estimated_days is NOT NULL at the DB level and still read as a
        // fallback by deliveryEstimateLabel() — this form never shows it
        // directly, so it must be derived automatically.
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post(route('admin.shipping-methods.store'), [
            'name_ar' => 'طريقة', 'name_en' => 'Ranged Method',
            'fee' => 50, 'delivery_time_min_days' => 3, 'delivery_time_max_days' => 7,
        ]);

        $this->assertDatabaseHas('shipping_methods', ['name_en' => 'Ranged Method', 'estimated_days' => '3-7']);
    }

    public function test_creating_derives_a_single_day_estimated_days_string_when_min_equals_max(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post(route('admin.shipping-methods.store'), [
            'name_ar' => 'طريقة', 'name_en' => 'Same Day Method',
            'fee' => 300, 'delivery_time_min_days' => 1, 'delivery_time_max_days' => 1,
        ]);

        $this->assertDatabaseHas('shipping_methods', ['name_en' => 'Same Day Method', 'estimated_days' => '1']);
    }

    public function test_max_days_below_min_days_is_rejected(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->post(route('admin.shipping-methods.store'), [
            'name_ar' => 'طريقة', 'name_en' => 'Invalid Range Method',
            'fee' => 50, 'delivery_time_min_days' => 5, 'delivery_time_max_days' => 2,
        ]);

        $response->assertSessionHasErrors('delivery_time_max_days');
        $this->assertDatabaseMissing('shipping_methods', ['name_en' => 'Invalid Range Method']);
    }

    public function test_duplicate_code_is_rejected(): void
    {
        $admin = $this->makeAdmin();
        $this->makeMethod(['code' => 'express']);

        $response = $this->actingAs($admin)->post(route('admin.shipping-methods.store'), [
            'code' => 'express',
            'name_ar' => 'طريقة', 'name_en' => 'Duplicate Code Method',
            'fee' => 50, 'delivery_time_min_days' => 1, 'delivery_time_max_days' => 2,
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_admin_can_update_a_shipping_method(): void
    {
        $admin = $this->makeAdmin();
        $method = $this->makeMethod(['fee' => 60]);

        $response = $this->actingAs($admin)->put(route('admin.shipping-methods.update', $method), [
            'code' => $method->code,
            'name_ar' => $method->name_ar, 'name_en' => 'Updated Name',
            'fee' => 120,
            'delivery_time_min_days' => 4, 'delivery_time_max_days' => 6,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.shipping-methods.index'));
        $this->assertSame('Updated Name', $method->fresh()->name_en);
        $this->assertSame(120, $method->fresh()->fee);
        $this->assertSame('4-6', $method->fresh()->estimated_days);
    }

    public function test_the_edit_form_is_pre_filled_with_existing_values(): void
    {
        $admin = $this->makeAdmin();
        $method = $this->makeMethod(['name_en' => 'Prefill Method', 'fee' => 77]);

        $response = $this->actingAs($admin)->get(route('admin.shipping-methods.edit', $method));

        $response->assertOk();
        $response->assertSee('value="Prefill Method"', false);
        $response->assertSee('value="77"', false);
    }

    // ---------------------------------------------------------------
    // Delete — deliberately no "in use" block (see controller docblock)
    // ---------------------------------------------------------------

    public function test_admin_can_delete_a_shipping_method_never_used_by_any_order(): void
    {
        $admin = $this->makeAdmin();
        $method = $this->makeMethod();

        $response = $this->actingAs($admin)->delete(route('admin.shipping-methods.destroy', $method));

        $response->assertRedirect(route('admin.shipping-methods.index'));
        $this->assertDatabaseMissing('shipping_methods', ['id' => $method->id]);
    }

    /**
     * The real reason no block is needed: orders.shipping_method_id is
     * nullOnDelete, and every order already snapshots the method's own
     * name/fee/delivery estimate onto shipping_method_code/name/
     * shipping_delivery_min_days/max_days at checkout time — deleting the
     * live row doesn't change what a past order displays. This proves
     * both halves: deletion succeeds, and the order's own snapshot survives
     * untouched.
     */
    public function test_deleting_a_method_referenced_by_a_real_order_succeeds_and_leaves_the_orders_snapshot_intact(): void
    {
        $admin = $this->makeAdmin();
        $method = $this->makeMethod(['name_en' => 'Referenced Method']);

        $category = Category::create(['name_ar' => 'ف', 'name_en' => 'Cat', 'slug' => 'cat-'.uniqid(), 'is_active' => true, 'sort_order' => 1]);
        $product = Product::create(['category_id' => $category->id, 'name_ar' => 'م', 'name_en' => 'Prod', 'slug' => 'prod-'.uniqid(), 'price' => 500, 'is_active' => true, 'is_featured' => false]);
        $order = Order::create([
            'order_number' => 'ORD-'.uniqid(),
            'customer_name' => 'Test', 'customer_email' => 'test@example.com', 'customer_phone' => '010',
            'governorate' => 'Cairo', 'city' => 'Nasr City', 'address' => 'x',
            'subtotal' => 500, 'shipping_fee' => $method->fee, 'total' => 500 + $method->fee, 'status' => 'delivered',
            'payment_method' => Order::PAYMENT_METHOD_COD,
            'shipping_method_id' => $method->id,
            'shipping_method_code' => $method->code,
            'shipping_method_name' => $method->name_en,
            'shipping_delivery_min_days' => $method->delivery_time_min_days,
            'shipping_delivery_max_days' => $method->delivery_time_max_days,
        ]);
        $order->items()->create(['product_id' => $product->id, 'product_name' => $product->name_en, 'size' => 'M', 'price' => 500, 'quantity' => 1]);

        $response = $this->actingAs($admin)->delete(route('admin.shipping-methods.destroy', $method));

        $response->assertRedirect(route('admin.shipping-methods.index'));
        $this->assertDatabaseMissing('shipping_methods', ['id' => $method->id]);

        $order->refresh();
        $this->assertNull($order->shipping_method_id);
        $this->assertSame('Referenced Method', $order->shipping_method_name);
        $this->assertSame($method->code, $order->shipping_method_code);
    }

    // ---------------------------------------------------------------
    // Quick toggle
    // ---------------------------------------------------------------

    public function test_toggle_active_flips_an_active_method_to_inactive(): void
    {
        $admin = $this->makeAdmin();
        $method = $this->makeMethod(['is_active' => true]);

        $response = $this->actingAs($admin)->patch(route('admin.shipping-methods.toggle-active', $method));

        $response->assertRedirect();
        $this->assertFalse($method->fresh()->is_active);
    }

    public function test_toggle_active_flips_an_inactive_method_back_to_active(): void
    {
        $admin = $this->makeAdmin();
        $method = $this->makeMethod(['is_active' => false]);

        $this->actingAs($admin)->patch(route('admin.shipping-methods.toggle-active', $method));

        $this->assertTrue($method->fresh()->is_active);
    }

    public function test_toggling_the_last_active_method_off_does_not_break_checkout_thanks_to_the_existing_self_heal(): void
    {
        // Not a guard this feature needs to add — ShippingMethod::
        // ensureAtLeastOneActive() (already wired into CheckoutController::
        // show()) already recreates a safe default whenever none are
        // active. Confirms that pre-existing safety net still engages
        // after using the new toggle.
        $admin = $this->makeAdmin();
        $method = $this->makeMethod(['code' => ShippingMethod::DEFAULT_CODE, 'is_active' => true]);

        $this->actingAs($admin)->patch(route('admin.shipping-methods.toggle-active', $method));
        $this->assertFalse($method->fresh()->is_active);

        ShippingMethod::ensureAtLeastOneActive();

        $this->assertTrue($method->fresh()->is_active);
        $this->assertSame(1, ShippingMethod::where('is_active', true)->count());
    }
}
