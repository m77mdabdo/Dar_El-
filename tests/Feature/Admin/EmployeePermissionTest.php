<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmployeePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function makeEmployee(): User
    {
        $employee = User::factory()->create();
        $employee->assignRole(Role::findOrCreate('employee', 'web'));

        return $employee;
    }

    /**
     * Mirrors how these tests bootstrap a bare role via
     * Role::findOrCreate() — RefreshDatabase wipes the permissions table
     * too, so a permission must exist before it can be granted.
     */
    protected function grant(User $user, string $permission): void
    {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    protected function makeOrder(): Order
    {
        $category = Category::create(['name_ar' => 'فئة', 'name_en' => 'Category', 'slug' => 'cat-'.uniqid(), 'is_active' => true, 'sort_order' => 1]);
        $product = Product::create([
            'category_id' => $category->id,
            'name_ar' => 'منتج', 'name_en' => 'Product',
            'slug' => 'product-'.uniqid(), 'price' => 100, 'is_active' => true, 'is_featured' => false,
        ]);

        return Order::create([
            'order_number' => 'ORD-'.uniqid(),
            'customer_name' => 'Test Customer',
            'customer_email' => 'customer@example.com',
            'customer_phone' => '01000000000',
            'governorate' => 'Cairo',
            'city' => 'Nasr City',
            'address' => '123 Test St',
            'subtotal' => 100,
            'shipping_fee' => 0,
            'discount_amount' => 0,
            'total' => 100,
            'status' => 'pending',
            'payment_method' => 'cod',
        ]);
    }

    public function test_employee_without_products_create_permission_is_blocked_from_the_create_form(): void
    {
        $employee = $this->makeEmployee();

        $this->actingAs($employee)->get('/admin/products/create')->assertForbidden();
    }

    public function test_employee_granted_products_create_permission_can_reach_the_create_form(): void
    {
        $employee = $this->makeEmployee();
        $this->grant($employee, 'products.create');

        $this->actingAs($employee)->get('/admin/products/create')->assertOk();
    }

    public function test_employee_without_orders_update_status_permission_is_blocked(): void
    {
        $employee = $this->makeEmployee();
        $order = $this->makeOrder();

        $response = $this->actingAs($employee)->patch("/admin/orders/{$order->id}/status", [
            'status' => 'processing',
        ]);

        $response->assertForbidden();
        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_employee_granted_orders_update_status_permission_can_update_status(): void
    {
        $employee = $this->makeEmployee();
        $this->grant($employee, 'orders.update_status');
        $order = $this->makeOrder();

        $response = $this->actingAs($employee)->patch("/admin/orders/{$order->id}/status", [
            'status' => 'processing',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertSame('processing', $order->fresh()->status);
    }

    public function test_employee_without_customers_view_permission_is_blocked_from_customer_list(): void
    {
        $employee = $this->makeEmployee();

        $this->actingAs($employee)->get('/admin/customers')->assertForbidden();
    }

    public function test_employee_granted_customers_view_permission_can_see_customer_list(): void
    {
        $employee = $this->makeEmployee();
        $this->grant($employee, 'customers.view');

        $this->actingAs($employee)->get('/admin/customers')->assertOk();
    }

    public function test_sidebar_hides_links_the_employee_has_no_permission_for(): void
    {
        $employee = $this->makeEmployee();
        $this->grant($employee, 'products.view');

        // Hitting /admin/products rather than /admin/dashboard deliberately
        // — the dashboard's own KPI widgets legitimately link to
        // admin.customers.index/coupons.index regardless of the viewer's
        // permissions (a separate, not-yet-scoped gap), which would give
        // this assertion false negatives on that page specifically.
        $response = $this->actingAs($employee)->get('/admin/products');

        // Checking route hrefs rather than translated labels — "nav.customers"
        // (العملاء) is coincidentally a substring of the unrelated
        // "nav.testimonials" label ("آراء العملاء"), which stays visible
        // (no permission gate) and would make a text-based assertDontSee
        // falsely fail even though the actual customers/coupons links are
        // correctly absent.
        $response->assertOk();
        $response->assertSee('href="'.route('admin.products.index').'"', false);
        $response->assertDontSee('href="'.route('admin.customers.index').'"', false);
        $response->assertDontSee('href="'.route('admin.coupons.index').'"', false);
    }
}
