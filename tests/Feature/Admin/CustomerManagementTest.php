<?php

namespace Tests\Feature\Admin;

use App\Models\Cart;
use App\Models\CartReminder;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    protected function customer(array $overrides = []): User
    {
        Role::findOrCreate('customer', 'web');
        $customer = User::factory()->create($overrides);
        $customer->assignRole('customer');

        return $customer;
    }

    public function test_non_admin_cannot_access_customer_routes(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer)->get(route('admin.customers.index'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.customers.show', $customer))->assertForbidden();
    }

    public function test_index_search_narrows_results(): void
    {
        $admin = $this->admin();
        $match = $this->customer(['name' => 'Layla Hassan', 'email' => 'layla@example.com']);
        $other = $this->customer(['name' => 'Somebody Else', 'email' => 'else@example.com']);

        $response = $this->actingAs($admin)->get(route('admin.customers.index', ['search' => 'Layla']));

        $response->assertOk();
        $response->assertSee('Layla Hassan');
        $response->assertDontSee('Somebody Else');
    }

    public function test_index_verified_filter_narrows_results(): void
    {
        $admin = $this->admin();
        $verified = $this->customer(['name' => 'Verified Customer', 'email_verified_at' => now()]);
        $unverified = $this->customer(['name' => 'Unverified Customer', 'email_verified_at' => null]);

        $response = $this->actingAs($admin)->get(route('admin.customers.index', ['verified' => '1']));

        $response->assertOk();
        $response->assertSee('Verified Customer');
        $response->assertDontSee('Unverified Customer');
    }

    public function test_disabling_a_customer_blocks_their_next_login(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();

        $this->actingAs($admin)
            ->patch(route('admin.customers.toggle-disabled', $customer))
            ->assertRedirect();

        $this->assertTrue($customer->fresh()->isDisabled());

        // actingAs() pins the guard's user for every subsequent request in
        // this test, so the admin session must be cleared before attempting
        // to log in as the (now disabled) customer.
        $this->app['auth']->guard()->logout();

        $this->post('/login', [
            'email' => $customer->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_re_enabling_a_customer_allows_login_again(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();

        $this->actingAs($admin)->patch(route('admin.customers.toggle-disabled', $customer));
        $this->actingAs($admin)->patch(route('admin.customers.toggle-disabled', $customer));

        $this->assertFalse($customer->fresh()->isDisabled());

        $this->app['auth']->guard()->logout();

        $this->post('/login', [
            'email' => $customer->email,
            'password' => 'password',
        ])->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($customer->fresh());
    }

    public function test_deleting_a_customer_with_orders_is_blocked(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();

        Order::create([
            'user_id' => $customer->id,
            'order_number' => 'ORD-TEST-'.uniqid(),
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => '01000000000',
            'governorate' => 'Cairo',
            'city' => 'Nasr City',
            'address' => '123 Test St',
            'subtotal' => 500,
            'shipping_fee' => 0,
            'discount_amount' => 0,
            'total' => 500,
            'status' => 'pending',
            'payment_method' => 'cod',
        ]);

        $this->actingAs($admin)->delete(route('admin.customers.destroy', $customer))->assertRedirect();

        $this->assertNotNull($customer->fresh());
    }

    public function test_deleting_a_customer_without_orders_succeeds(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();

        $this->actingAs($admin)
            ->delete(route('admin.customers.destroy', $customer))
            ->assertRedirect(route('admin.customers.index'));

        $this->assertNull($customer->fresh());
    }

    public function test_manual_reminder_creates_cart_reminder_and_increments_counter(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();

        $category = Category::create(['name_ar' => 'فئة', 'name_en' => 'Category', 'slug' => 'category-'.uniqid()]);
        $product = Product::create([
            'category_id' => $category->id,
            'name_ar' => 'منتج', 'name_en' => 'Product', 'slug' => 'product-'.uniqid(),
            'price' => 500, 'is_active' => true, 'is_featured' => false,
        ]);

        $cart = Cart::create([
            'user_id' => $customer->id,
            'status' => 'active',
            'subtotal' => 500,
            'total' => 500,
            'items_count' => 1,
            'last_activity_at' => now(),
        ]);
        $cart->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name_en,
            'quantity' => 1,
            'price' => 500,
            'total' => 500,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.customers.send-reminder', $customer))
            ->assertRedirect();

        $cart->refresh();
        $this->assertSame('abandoned', $cart->status);
        $this->assertSame(1, $cart->reminder_count);
        $this->assertSame(1, CartReminder::where('cart_id', $cart->id)->count());
    }
}
