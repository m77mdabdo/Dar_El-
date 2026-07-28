<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportProductsTest extends TestCase
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

    protected function makeProduct(string $name): Product
    {
        $category = Category::create(['name_ar' => 'ف', 'name_en' => 'Cat', 'slug' => 'cat-'.uniqid(), 'is_active' => true, 'sort_order' => 1]);

        return Product::create(['category_id' => $category->id, 'name_ar' => $name, 'name_en' => $name, 'slug' => 'p-'.uniqid(), 'price' => 100, 'is_active' => true, 'is_featured' => false]);
    }

    protected function orderFor(Product $product, int $quantity, int $price, string $status = 'delivered', ?string $createdAt = null): Order
    {
        $order = Order::create([
            'order_number' => 'ORD-'.uniqid(),
            'customer_name' => 'Test', 'customer_email' => 'test@example.com', 'customer_phone' => '010',
            'governorate' => 'Cairo', 'city' => 'Nasr City', 'address' => 'x',
            'subtotal' => $price * $quantity, 'shipping_fee' => 0, 'total' => $price * $quantity, 'status' => $status, 'payment_method' => 'cod',
        ]);
        if ($createdAt) {
            $order->forceFill(['created_at' => $createdAt])->save();
        }
        OrderItem::create(['order_id' => $order->id, 'product_id' => $product->id, 'product_name' => $product->name_en, 'size' => 'M', 'price' => $price, 'quantity' => $quantity]);

        return $order;
    }

    // ---------------------------------------------------------------
    // Access control
    // ---------------------------------------------------------------

    public function test_a_bare_employee_without_the_permission_is_forbidden(): void
    {
        $employee = $this->makeEmployee();

        $this->actingAs($employee)->get(route('admin.reports.products'))->assertForbidden();
    }

    public function test_an_employee_granted_reports_products_can_view_it(): void
    {
        $employee = $this->makeEmployee();
        $this->grant($employee, 'reports.products');

        $this->actingAs($employee)->get(route('admin.reports.products'))->assertOk();
    }

    public function test_admin_can_view_it_regardless_of_seeded_permissions(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get(route('admin.reports.products'))->assertOk();
    }

    // ---------------------------------------------------------------
    // Data correctness
    // ---------------------------------------------------------------

    public function test_top_sellers_by_quantity_are_ranked_correctly(): void
    {
        $admin = $this->makeAdmin();
        $bestSeller = $this->makeProduct('Best Seller Product');
        $worstSeller = $this->makeProduct('Worst Seller Product');
        $this->orderFor($bestSeller, 10, 100);
        $this->orderFor($worstSeller, 1, 100);

        $response = $this->actingAs($admin)->get(route('admin.reports.products'));

        $response->assertOk();
        $response->assertSeeInOrder(['Best Seller Product', 'Worst Seller Product']);
    }

    public function test_top_sellers_by_revenue_can_differ_from_by_quantity(): void
    {
        $admin = $this->makeAdmin();
        // Sells more units but is cheap.
        $highQuantity = $this->makeProduct('High Quantity Cheap Product');
        $this->orderFor($highQuantity, 100, 1);
        // Sells few units but is expensive — higher total revenue.
        $highRevenue = $this->makeProduct('High Revenue Expensive Product');
        $this->orderFor($highRevenue, 1, 5000);

        $response = $this->actingAs($admin)->get(route('admin.reports.products'));

        $response->assertOk();
        $html = $response->getContent();

        // By quantity: cheap-but-popular product ranks first.
        $qtySectionStart = strpos($html, __('reports.products_top_by_quantity'));
        $revenueSectionStart = strpos($html, __('reports.products_top_by_revenue'));
        $qtySection = substr($html, $qtySectionStart, $revenueSectionStart - $qtySectionStart);
        $this->assertLessThan(
            strpos($qtySection, 'High Revenue Expensive Product'),
            strpos($qtySection, 'High Quantity Cheap Product')
        );

        // By revenue: expensive-but-rare product ranks first instead.
        $worstSectionStart = strpos($html, __('reports.products_worst_by_quantity'));
        $revenueSection = substr($html, $revenueSectionStart, $worstSectionStart - $revenueSectionStart);
        $this->assertLessThan(
            strpos($revenueSection, 'High Quantity Cheap Product'),
            strpos($revenueSection, 'High Revenue Expensive Product')
        );
    }

    public function test_worst_sellers_by_quantity_are_ascending(): void
    {
        $admin = $this->makeAdmin();
        $sellsMore = $this->makeProduct('Sells More Product');
        $sellsLess = $this->makeProduct('Sells Less Product');
        $this->orderFor($sellsMore, 20, 100);
        $this->orderFor($sellsLess, 2, 100);

        $response = $this->actingAs($admin)->get(route('admin.reports.products'));

        $response->assertOk();
        $html = $response->getContent();
        $worstSectionStart = strpos($html, __('reports.products_worst_by_quantity'));
        $worstSection = substr($html, $worstSectionStart);

        $this->assertLessThan(
            strpos($worstSection, 'Sells More Product'),
            strpos($worstSection, 'Sells Less Product')
        );
    }

    public function test_cancelled_orders_are_excluded_from_all_three_sections(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct('Only In A Cancelled Order');
        $this->orderFor($product, 5, 100, status: 'cancelled');

        $response = $this->actingAs($admin)->get(route('admin.reports.products'));

        $response->assertOk();
        $response->assertDontSee('Only In A Cancelled Order');
    }

    public function test_a_custom_date_range_is_respected(): void
    {
        $admin = $this->makeAdmin();
        $inRange = $this->makeProduct('In Range Product');
        $this->orderFor($inRange, 3, 100, createdAt: '2026-01-15 12:00:00');
        $outOfRange = $this->makeProduct('Out Of Range Product');
        $this->orderFor($outOfRange, 3, 100, createdAt: '2026-03-01 12:00:00');

        $response = $this->actingAs($admin)->get(route('admin.reports.products', ['from' => '2026-01-01', 'to' => '2026-01-31']));

        $response->assertOk();
        $response->assertSee('In Range Product');
        $response->assertDontSee('Out Of Range Product');
    }
}
