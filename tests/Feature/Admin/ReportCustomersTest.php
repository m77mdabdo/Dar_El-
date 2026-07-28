<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportCustomersTest extends TestCase
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

    protected function orderFor(User $customer, int $total, string $status = 'delivered', ?string $createdAt = null): Order
    {
        $order = Order::create([
            'user_id' => $customer->id,
            'order_number' => 'ORD-'.uniqid(),
            'customer_name' => $customer->name, 'customer_email' => $customer->email, 'customer_phone' => '010',
            'governorate' => 'Cairo', 'city' => 'Nasr City', 'address' => 'x',
            'subtotal' => $total, 'shipping_fee' => 0, 'total' => $total, 'status' => $status, 'payment_method' => 'cod',
        ]);
        if ($createdAt) {
            $order->forceFill(['created_at' => $createdAt])->save();
        }

        return $order;
    }

    // ---------------------------------------------------------------
    // Access control
    // ---------------------------------------------------------------

    public function test_a_bare_employee_without_the_permission_is_forbidden(): void
    {
        $employee = $this->makeEmployee();

        $this->actingAs($employee)->get(route('admin.reports.customers'))->assertForbidden();
    }

    public function test_an_employee_granted_reports_customers_can_view_it(): void
    {
        $employee = $this->makeEmployee();
        $this->grant($employee, 'reports.customers');

        $this->actingAs($employee)->get(route('admin.reports.customers'))->assertOk();
    }

    public function test_admin_can_view_it_regardless_of_seeded_permissions(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get(route('admin.reports.customers'))->assertOk();
    }

    // ---------------------------------------------------------------
    // Data correctness
    // ---------------------------------------------------------------

    public function test_top_customers_by_order_count_are_ranked_correctly(): void
    {
        $admin = $this->makeAdmin();
        $frequent = User::factory()->create(['name' => 'Frequent Buyer']);
        $occasional = User::factory()->create(['name' => 'Occasional Buyer']);
        $this->orderFor($frequent, 100);
        $this->orderFor($frequent, 100);
        $this->orderFor($frequent, 100);
        $this->orderFor($occasional, 100);

        $response = $this->actingAs($admin)->get(route('admin.reports.customers'));

        $response->assertOk();
        $html = $response->getContent();
        $ordersSectionStart = strpos($html, __('reports.customers_top_by_orders'));
        $spendSectionStart = strpos($html, __('reports.customers_top_by_spend'));
        $ordersSection = substr($html, $ordersSectionStart, $spendSectionStart - $ordersSectionStart);
        $this->assertLessThan(
            strpos($ordersSection, 'Occasional Buyer'),
            strpos($ordersSection, 'Frequent Buyer')
        );
    }

    public function test_top_customers_by_spend_can_differ_from_by_order_count(): void
    {
        $admin = $this->makeAdmin();
        // Orders often but cheaply.
        $frequentCheap = User::factory()->create(['name' => 'Frequent Cheap Buyer']);
        $this->orderFor($frequentCheap, 10);
        $this->orderFor($frequentCheap, 10);
        $this->orderFor($frequentCheap, 10);
        // Orders once but big — higher total spend.
        $rareExpensive = User::factory()->create(['name' => 'Rare Big Spender']);
        $this->orderFor($rareExpensive, 5000);

        $response = $this->actingAs($admin)->get(route('admin.reports.customers'));

        $response->assertOk();
        $html = $response->getContent();

        $spendSectionStart = strpos($html, __('reports.customers_top_by_spend'));
        $spendSection = substr($html, $spendSectionStart);
        $this->assertLessThan(
            strpos($spendSection, 'Frequent Cheap Buyer'),
            strpos($spendSection, 'Rare Big Spender')
        );
    }

    public function test_cancelled_orders_are_excluded_from_ranking_tables(): void
    {
        $admin = $this->makeAdmin();
        $customer = User::factory()->create(['name' => 'Only Cancelled Orders']);
        $this->orderFor($customer, 100, status: 'cancelled');

        $response = $this->actingAs($admin)->get(route('admin.reports.customers'));

        $response->assertOk();
        $response->assertDontSee('Only Cancelled Orders');
    }

    public function test_a_custom_date_range_is_respected(): void
    {
        $admin = $this->makeAdmin();
        $inRange = User::factory()->create(['name' => 'In Range Customer']);
        $this->orderFor($inRange, 100, createdAt: '2026-01-15 12:00:00');
        $outOfRange = User::factory()->create(['name' => 'Out Of Range Customer']);
        $this->orderFor($outOfRange, 100, createdAt: '2026-03-01 12:00:00');

        $response = $this->actingAs($admin)->get(route('admin.reports.customers', ['from' => '2026-01-01', 'to' => '2026-01-31']));

        $response->assertOk();
        $response->assertSee('In Range Customer');
        $response->assertDontSee('Out Of Range Customer');
    }

    public function test_a_customer_whose_first_order_falls_in_range_counts_as_new(): void
    {
        $admin = $this->makeAdmin();
        $customer = User::factory()->create();
        $this->orderFor($customer, 100, createdAt: '2026-01-15 12:00:00');

        $response = $this->actingAs($admin)->get(route('admin.reports.customers', ['from' => '2026-01-01', 'to' => '2026-01-31']));

        $response->assertOk();
        $response->assertSeeText(__('reports.customers_new'));
        $this->assertEquals(1, $response->viewData('newCustomers'));
        $this->assertEquals(0, $response->viewData('returningCustomers'));
    }

    public function test_a_customer_with_a_prior_order_before_the_range_counts_as_returning(): void
    {
        $admin = $this->makeAdmin();
        $customer = User::factory()->create();
        $this->orderFor($customer, 100, createdAt: '2025-06-01 12:00:00');
        $this->orderFor($customer, 100, createdAt: '2026-01-15 12:00:00');

        $response = $this->actingAs($admin)->get(route('admin.reports.customers', ['from' => '2026-01-01', 'to' => '2026-01-31']));

        $response->assertOk();
        $this->assertEquals(0, $response->viewData('newCustomers'));
        $this->assertEquals(1, $response->viewData('returningCustomers'));
    }
}
