<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Notifications\ProductLowStock;
use App\Notifications\ProductOutOfStock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The cross-product stock overview that replaced the "Coming Soon"
 * Inventory sidebar placeholder — distinct from the per-product "Sizes &
 * Stock" tab (ProductSizesStockTest), which edits one product at a time.
 * The inline-edit action here is the exact same
 * Admin\ProductController::updateSizes() that tab already uses (just
 * called via fetch() with an Accept: application/json header instead of a
 * full-page form post), so the low-stock/out-of-stock alert coverage below
 * is really proving "this didn't fork the logic," not testing it fresh.
 */
class InventoryTest extends TestCase
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

    protected function makeCategory(string $name = 'Category'): Category
    {
        return Category::create([
            'name_ar' => $name, 'name_en' => $name, 'slug' => 'cat-'.uniqid(),
            'is_active' => true, 'sort_order' => 1,
        ]);
    }

    protected function makeProduct(string $name, ?Category $category = null): Product
    {
        return Product::create([
            'category_id' => ($category ?? $this->makeCategory())->id,
            'name_ar' => $name, 'name_en' => $name, 'slug' => 'product-'.uniqid(),
            'price' => 500, 'is_active' => true, 'is_featured' => false,
        ]);
    }

    // ---------------------------------------------------------------
    // Access control
    // ---------------------------------------------------------------

    public function test_a_bare_employee_without_the_permission_is_forbidden(): void
    {
        $employee = $this->makeEmployee();

        $this->actingAs($employee)->get(route('admin.inventory.index'))->assertForbidden();
    }

    public function test_an_employee_granted_inventory_view_can_see_the_page(): void
    {
        $employee = $this->makeEmployee();
        $this->grant($employee, 'inventory.view');
        $this->makeProduct('Grantee Visible Product');

        $response = $this->actingAs($employee)->get(route('admin.inventory.index'));

        $response->assertOk();
        $response->assertSee('Grantee Visible Product');
    }

    public function test_admin_can_view_it_regardless_of_seeded_permissions(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get(route('admin.inventory.index'))->assertOk();
    }

    // ---------------------------------------------------------------
    // Listing
    // ---------------------------------------------------------------

    public function test_lists_products_with_their_per_size_stock(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct('Layered Abaya');
        $product->sizes()->create(['size' => 'M', 'stock' => 12]);
        $product->sizes()->create(['size' => 'L', 'stock' => 3]);

        $response = $this->actingAs($admin)->get(route('admin.inventory.index'));

        $response->assertOk();
        $response->assertSee('Layered Abaya');
        $response->assertSee('value="12"', false);
        $response->assertSee('value="3"', false);
    }

    public function test_a_product_with_no_sizes_shows_a_no_sizes_indicator_instead_of_a_broken_form(): void
    {
        $admin = $this->makeAdmin();
        $this->makeProduct('Sizeless Product');

        $response = $this->actingAs($admin)->get(route('admin.inventory.index'));

        $response->assertOk();
        $response->assertSee(__('inventory.no_sizes_short'));
    }

    public function test_search_filters_by_product_name(): void
    {
        $admin = $this->makeAdmin();
        $this->makeProduct('Findable Kaftan');
        $this->makeProduct('Other Item');

        $response = $this->actingAs($admin)->get(route('admin.inventory.index', ['search' => 'Findable']));

        $response->assertOk();
        $response->assertSee('Findable Kaftan');
        $response->assertDontSee('Other Item');
    }

    public function test_category_filter_shows_only_that_categorys_products(): void
    {
        $admin = $this->makeAdmin();
        $categoryA = $this->makeCategory('Abayas');
        $categoryB = $this->makeCategory('Scarves');
        $this->makeProduct('Abaya Product', $categoryA);
        $this->makeProduct('Scarf Product', $categoryB);

        $response = $this->actingAs($admin)->get(route('admin.inventory.index', ['category_id' => $categoryA->id]));

        $response->assertOk();
        $response->assertSee('Abaya Product');
        $response->assertDontSee('Scarf Product');
    }

    public function test_low_stock_filter_excludes_healthy_and_out_of_stock_products(): void
    {
        $admin = $this->makeAdmin();
        $low = $this->makeProduct('Low Stock Item');
        $low->sizes()->create(['size' => 'M', 'stock' => 2]);
        $healthy = $this->makeProduct('Healthy Stock Item');
        $healthy->sizes()->create(['size' => 'M', 'stock' => 50]);
        $out = $this->makeProduct('Out Of Stock Item');
        $out->sizes()->create(['size' => 'M', 'stock' => 0]);

        $response = $this->actingAs($admin)->get(route('admin.inventory.index', ['stock_status' => 'low_stock']));

        $response->assertOk();
        $response->assertSee('Low Stock Item');
        $response->assertDontSee('Healthy Stock Item');
        $response->assertDontSee('Out Of Stock Item');
    }

    public function test_out_of_stock_filter_shows_only_products_with_zero_total_stock(): void
    {
        $admin = $this->makeAdmin();
        $out = $this->makeProduct('Truly Empty Item');
        $out->sizes()->create(['size' => 'M', 'stock' => 0]);
        $healthy = $this->makeProduct('Well Stocked Item');
        $healthy->sizes()->create(['size' => 'M', 'stock' => 20]);

        $response = $this->actingAs($admin)->get(route('admin.inventory.index', ['stock_status' => 'out_of_stock']));

        $response->assertOk();
        $response->assertSee('Truly Empty Item');
        $response->assertDontSee('Well Stocked Item');
    }

    public function test_sorting_by_stock_ascending_orders_lowest_total_first(): void
    {
        $admin = $this->makeAdmin();
        $high = $this->makeProduct('High Stock Product');
        $high->sizes()->create(['size' => 'M', 'stock' => 40]);
        $low = $this->makeProduct('Low Stock Product');
        $low->sizes()->create(['size' => 'M', 'stock' => 2]);

        $response = $this->actingAs($admin)->get(route('admin.inventory.index', ['sort' => 'stock_asc']));

        $response->assertOk();
        $content = $response->getContent();
        $this->assertLessThan(strpos($content, 'High Stock Product'), strpos($content, 'Low Stock Product'));
    }

    public function test_sorting_by_stock_descending_orders_highest_total_first(): void
    {
        $admin = $this->makeAdmin();
        $high = $this->makeProduct('High Stock Product');
        $high->sizes()->create(['size' => 'M', 'stock' => 40]);
        $low = $this->makeProduct('Low Stock Product');
        $low->sizes()->create(['size' => 'M', 'stock' => 2]);

        $response = $this->actingAs($admin)->get(route('admin.inventory.index', ['sort' => 'stock_desc']));

        $response->assertOk();
        $content = $response->getContent();
        $this->assertLessThan(strpos($content, 'Low Stock Product'), strpos($content, 'High Stock Product'));
    }

    // ---------------------------------------------------------------
    // Inline stock editing (shared action with the Sizes & Stock tab)
    // ---------------------------------------------------------------

    public function test_inline_json_update_persists_the_new_stock_and_returns_the_recomputed_status(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct('Inline Edit Product');
        $size = $product->sizes()->create(['size' => 'M', 'stock' => 10]);

        $response = $this->actingAs($admin)->patchJson(route('admin.products.sizes.update', $product), [
            'sizes' => ['M' => 30],
        ]);

        $response->assertOk();
        $response->assertJson([
            'status' => 'ok',
            'total_stock' => 30,
            'stock_status' => ['status' => 'in_stock'],
            'sizes' => ['M' => 30],
        ]);
        $this->assertSame(30, $size->fresh()->stock);
    }

    public function test_inline_json_update_reports_low_stock_status_when_crossed(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct('Threshold Product');
        $product->sizes()->create(['size' => 'M', 'stock' => 10]);

        $response = $this->actingAs($admin)->patchJson(route('admin.products.sizes.update', $product), [
            'sizes' => ['M' => 3],
        ]);

        $response->assertOk();
        $response->assertJsonPath('stock_status.status', 'low_stock');
    }

    public function test_inline_update_still_triggers_the_low_stock_admin_alert(): void
    {
        Notification::fake();
        $admin = $this->makeAdmin();
        $product = $this->makeProduct('Alert Product');
        $product->sizes()->create(['size' => 'M', 'stock' => 10]);

        $this->actingAs($admin)->patchJson(route('admin.products.sizes.update', $product), [
            'sizes' => ['M' => 2],
        ])->assertOk();

        Notification::assertSentTo(User::admins(), ProductLowStock::class);
    }

    public function test_inline_update_still_triggers_the_out_of_stock_admin_alert(): void
    {
        Notification::fake();
        $admin = $this->makeAdmin();
        $product = $this->makeProduct('Alert Product 2');
        $product->sizes()->create(['size' => 'M', 'stock' => 10]);

        $this->actingAs($admin)->patchJson(route('admin.products.sizes.update', $product), [
            'sizes' => ['M' => 0],
        ])->assertOk();

        Notification::assertSentTo(User::admins(), ProductOutOfStock::class);
    }

    public function test_the_non_json_form_post_still_redirects_back_unaffected(): void
    {
        // The Sizes & Stock tab's own coverage (ProductSizesStockTest)
        // already exercises this path fully — this just confirms adding
        // the JSON branch didn't change its behavior for a plain form post.
        $admin = $this->makeAdmin();
        $product = $this->makeProduct('Classic Form Product');
        $size = $product->sizes()->create(['size' => 'M', 'stock' => 10]);

        $response = $this->actingAs($admin)->patch(route('admin.products.sizes.update', $product), [
            'sizes' => ['M' => 25],
        ]);

        $response->assertRedirect();
        $this->assertSame(25, $size->fresh()->stock);
    }
}
