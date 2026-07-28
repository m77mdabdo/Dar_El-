<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportInventoryTest extends TestCase
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

    protected function makeProduct(string $nameEn, int $stock, int $price = 100): Product
    {
        $category = Category::create(['name_ar' => 'فئة', 'name_en' => 'Category', 'slug' => 'cat-'.uniqid(), 'is_active' => true, 'sort_order' => 1]);
        $product = Product::create(['category_id' => $category->id, 'name_ar' => $nameEn, 'name_en' => $nameEn, 'slug' => 'product-'.uniqid(), 'price' => $price, 'is_active' => true, 'is_featured' => false]);
        $product->sizes()->create(['size' => 'M', 'stock' => $stock]);

        return $product;
    }

    // ---------------------------------------------------------------
    // Access control
    // ---------------------------------------------------------------

    public function test_a_bare_employee_without_the_permission_is_forbidden(): void
    {
        $employee = $this->makeEmployee();

        $this->actingAs($employee)->get(route('admin.reports.inventory'))->assertForbidden();
    }

    public function test_an_employee_granted_reports_inventory_can_view_it(): void
    {
        $employee = $this->makeEmployee();
        $this->grant($employee, 'reports.inventory');

        $this->actingAs($employee)->get(route('admin.reports.inventory'))->assertOk();
    }

    public function test_admin_can_view_it_regardless_of_seeded_permissions(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get(route('admin.reports.inventory'))->assertOk();
    }

    // ---------------------------------------------------------------
    // Data correctness
    // ---------------------------------------------------------------

    public function test_products_are_ranked_by_stock_value_descending_by_default(): void
    {
        $admin = $this->makeAdmin();
        // 10 units x 500 = 5,000
        $highValue = $this->makeProduct('High Value Product', stock: 10, price: 500);
        // 100 units x 10 = 1,000
        $lowValue = $this->makeProduct('Low Value Product', stock: 100, price: 10);

        $response = $this->actingAs($admin)->get(route('admin.reports.inventory'));

        $response->assertOk();
        $response->assertSeeInOrder(['High Value Product', 'Low Value Product']);
    }

    public function test_the_total_valuation_stat_sums_quantity_times_price_across_all_products(): void
    {
        $admin = $this->makeAdmin();
        $this->makeProduct('Product A', stock: 10, price: 500);
        $this->makeProduct('Product B', stock: 100, price: 10);

        $response = $this->actingAs($admin)->get(route('admin.reports.inventory'));

        $response->assertOk();
        $this->assertEquals(6000, $response->viewData('totalValuation'));
    }

    public function test_low_and_out_of_stock_counts_reflect_the_shared_stock_status_scope(): void
    {
        $admin = $this->makeAdmin();
        $this->makeProduct('In Stock Product', stock: 50);
        $this->makeProduct('Low Stock Product', stock: 2);
        $this->makeProduct('Out Of Stock Product', stock: 0);

        $response = $this->actingAs($admin)->get(route('admin.reports.inventory'));

        $response->assertOk();
        $this->assertEquals(1, $response->viewData('lowStockCount'));
        $this->assertEquals(1, $response->viewData('outOfStockCount'));
    }

    public function test_the_stock_status_filter_narrows_the_table(): void
    {
        $admin = $this->makeAdmin();
        $this->makeProduct('In Stock Product', stock: 50);
        $this->makeProduct('Out Of Stock Product', stock: 0);

        $response = $this->actingAs($admin)->get(route('admin.reports.inventory', ['stock_status' => 'out_of_stock']));

        $response->assertOk();
        $response->assertSee('Out Of Stock Product');
        $response->assertDontSee('In Stock Product');
    }

    public function test_sort_stock_asc_orders_by_lowest_stock_first(): void
    {
        $admin = $this->makeAdmin();
        $this->makeProduct('High Stock Product', stock: 50);
        $this->makeProduct('Zero Stock Product', stock: 0);

        $response = $this->actingAs($admin)->get(route('admin.reports.inventory', ['sort' => 'stock_asc']));

        $response->assertOk();
        $response->assertSeeInOrder(['Zero Stock Product', 'High Stock Product']);
    }
}
