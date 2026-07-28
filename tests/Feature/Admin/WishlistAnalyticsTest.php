<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Cross-product wishlist analytics — genuinely new (per-customer wishlist
 * visibility already existed via Admin\CustomerController::wishlist(),
 * but nothing ranked "most wishlisted products store-wide" or
 * cross-referenced that against stock). Shared by two sidebar entries
 * (Marketing > Wishlist, Reports > Wishlist), both pre-wired to the
 * 'reports.wishlist' permission slug.
 */
class WishlistAnalyticsTest extends TestCase
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

    protected function makeProduct(string $nameEn, int $stock = 20): Product
    {
        // name_ar matches name_en exactly — the admin view is locale-aware
        // (trans_field), and the test suite's default locale is Arabic, so
        // divergent AR/EN values would make assertSee($nameEn) fail
        // depending on which name happens to render.
        $category = Category::create(['name_ar' => 'فئة', 'name_en' => 'Category', 'slug' => 'cat-'.uniqid(), 'is_active' => true, 'sort_order' => 1]);
        $product = Product::create(['category_id' => $category->id, 'name_ar' => $nameEn, 'name_en' => $nameEn, 'slug' => 'product-'.uniqid(), 'price' => 300, 'is_active' => true, 'is_featured' => false]);
        $product->sizes()->create(['size' => 'M', 'stock' => $stock]);

        return $product;
    }

    protected function wishlistedBy(Product $product, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $user = User::factory()->create();
            $user->assignRole(Role::findOrCreate('customer', 'web'));
            Wishlist::create(['user_id' => $user->id, 'product_id' => $product->id]);
        }
    }

    // ---------------------------------------------------------------
    // Access control
    // ---------------------------------------------------------------

    public function test_a_bare_employee_without_the_permission_is_forbidden(): void
    {
        $employee = $this->makeEmployee();

        $this->actingAs($employee)->get(route('admin.wishlist-analytics.index'))->assertForbidden();
    }

    public function test_an_employee_granted_reports_wishlist_can_view_it(): void
    {
        $employee = $this->makeEmployee();
        $this->grant($employee, 'reports.wishlist');
        $product = $this->makeProduct('Employee Visible Product');
        $this->wishlistedBy($product, 1);

        $response = $this->actingAs($employee)->get(route('admin.wishlist-analytics.index'));

        $response->assertOk();
        $response->assertSee('Employee Visible Product');
    }

    public function test_admin_can_view_it_regardless_of_seeded_permissions(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get(route('admin.wishlist-analytics.index'))->assertOk();
    }

    // ---------------------------------------------------------------
    // Data correctness
    // ---------------------------------------------------------------

    public function test_only_products_with_at_least_one_wishlist_add_are_listed(): void
    {
        $admin = $this->makeAdmin();
        $wishlisted = $this->makeProduct('Wishlisted Product');
        $notWishlisted = $this->makeProduct('Never Wishlisted Product');
        $this->wishlistedBy($wishlisted, 1);

        $response = $this->actingAs($admin)->get(route('admin.wishlist-analytics.index'));

        $response->assertOk();
        $response->assertSee('Wishlisted Product');
        $response->assertDontSee('Never Wishlisted Product');
    }

    public function test_products_are_ranked_by_wishlist_count_descending_by_default(): void
    {
        $admin = $this->makeAdmin();
        $mostWishlisted = $this->makeProduct('Most Wishlisted');
        $leastWishlisted = $this->makeProduct('Least Wishlisted');
        $this->wishlistedBy($mostWishlisted, 5);
        $this->wishlistedBy($leastWishlisted, 1);

        $response = $this->actingAs($admin)->get(route('admin.wishlist-analytics.index'));

        $response->assertOk();
        $response->assertSeeInOrder(['Most Wishlisted', 'Least Wishlisted']);
    }

    public function test_stat_cards_show_total_adds_and_low_out_of_stock_counts(): void
    {
        $admin = $this->makeAdmin();
        $lowStock = $this->makeProduct('Low Stock Wishlisted', stock: 2);
        $outOfStock = $this->makeProduct('Out Of Stock Wishlisted', stock: 0);
        $healthy = $this->makeProduct('Healthy Stock Wishlisted', stock: 50);
        $this->wishlistedBy($lowStock, 2);
        $this->wishlistedBy($outOfStock, 3);
        $this->wishlistedBy($healthy, 1);

        $response = $this->actingAs($admin)->get(route('admin.wishlist-analytics.index'));

        $response->assertOk();
        $response->assertSeeInOrder([__('admin_wishlist.stat_total_adds'), '6']);
        $response->assertSeeInOrder([__('admin_wishlist.stat_wishlisted_low_stock'), '1']);
        $response->assertSeeInOrder([__('admin_wishlist.stat_wishlisted_out_of_stock'), '1']);
    }

    public function test_stock_status_filter_narrows_the_list(): void
    {
        $admin = $this->makeAdmin();
        $lowStock = $this->makeProduct('Filter Low Stock', stock: 2);
        $healthy = $this->makeProduct('Filter Healthy Stock', stock: 50);
        $this->wishlistedBy($lowStock, 1);
        $this->wishlistedBy($healthy, 1);

        $response = $this->actingAs($admin)->get(route('admin.wishlist-analytics.index', ['stock_status' => 'low_stock']));

        $response->assertOk();
        $response->assertSee('Filter Low Stock');
        $response->assertDontSee('Filter Healthy Stock');
    }

    public function test_sort_by_lowest_stock_orders_ascending_by_stock(): void
    {
        $admin = $this->makeAdmin();
        $moreStock = $this->makeProduct('More Stock Item', stock: 10);
        $lessStock = $this->makeProduct('Less Stock Item', stock: 1);
        $this->wishlistedBy($moreStock, 1);
        $this->wishlistedBy($lessStock, 1);

        $response = $this->actingAs($admin)->get(route('admin.wishlist-analytics.index', ['sort' => 'stock_asc']));

        $response->assertOk();
        $response->assertSeeInOrder(['Less Stock Item', 'More Stock Item']);
    }
}
