<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Reports > Sales — a real, filterable drill-down (date range +
 * CSV export), deliberately not a repeat of the Dashboard's fixed
 * 14-day charts. Gated by 'reports.sales', its own specific slug
 * already pre-wired in config/admin_sidebar.php (not the Dashboard's
 * blanket 'reports.view').
 */
class ReportSalesTest extends TestCase
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

    protected function makeOrder(array $overrides = []): Order
    {
        return Order::create(array_merge([
            'order_number' => 'ORD-'.uniqid(),
            'customer_name' => 'Test', 'customer_email' => 'test@example.com', 'customer_phone' => '010',
            'governorate' => 'Cairo', 'city' => 'Nasr City', 'address' => 'x',
            'subtotal' => 500, 'shipping_fee' => 0, 'total' => 500, 'status' => 'delivered', 'payment_method' => 'cod',
        ], $overrides));
    }

    // ---------------------------------------------------------------
    // Access control
    // ---------------------------------------------------------------

    public function test_a_bare_employee_without_the_permission_is_forbidden(): void
    {
        $employee = $this->makeEmployee();

        $this->actingAs($employee)->get(route('admin.reports.sales'))->assertForbidden();
    }

    public function test_an_employee_granted_reports_sales_can_view_it(): void
    {
        $employee = $this->makeEmployee();
        $this->grant($employee, 'reports.sales');

        $this->actingAs($employee)->get(route('admin.reports.sales'))->assertOk();
    }

    public function test_admin_can_view_it_regardless_of_seeded_permissions(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get(route('admin.reports.sales'))->assertOk();
    }

    // ---------------------------------------------------------------
    // Data correctness
    // ---------------------------------------------------------------

    public function test_default_range_is_the_last_30_days_and_excludes_older_orders(): void
    {
        $admin = $this->makeAdmin();
        $recent = $this->makeOrder(['total' => 500]);
        $old = $this->makeOrder(['total' => 999]);
        $old->forceFill(['created_at' => now()->subDays(60)])->save();

        $response = $this->actingAs($admin)->get(route('admin.reports.sales'));

        $response->assertOk();
        $response->assertSeeInOrder([__('reports.sales_total_orders'), '1']);
    }

    public function test_a_custom_date_range_is_respected(): void
    {
        $admin = $this->makeAdmin();
        $inRange = $this->makeOrder(['total' => 500]);
        $inRange->forceFill(['created_at' => '2026-01-15 12:00:00'])->save();
        $outOfRange = $this->makeOrder(['total' => 999]);
        $outOfRange->forceFill(['created_at' => '2026-03-01 12:00:00'])->save();

        $response = $this->actingAs($admin)->get(route('admin.reports.sales', ['from' => '2026-01-01', 'to' => '2026-01-31']));

        $response->assertOk();
        $response->assertSeeInOrder([__('reports.sales_total_orders'), '1']);
        $response->assertSeeInOrder([__('reports.sales_total_revenue'), '500']);
    }

    public function test_cancelled_orders_are_counted_but_excluded_from_revenue(): void
    {
        $admin = $this->makeAdmin();
        $this->makeOrder(['total' => 500, 'status' => 'delivered']);
        $this->makeOrder(['total' => 1000, 'status' => 'cancelled']);

        $response = $this->actingAs($admin)->get(route('admin.reports.sales'));

        $response->assertOk();
        $response->assertSeeInOrder([__('reports.sales_total_orders'), '2']);
        $response->assertSeeInOrder([__('reports.sales_completed_orders'), '1']);
        $response->assertSeeInOrder([__('reports.sales_cancelled_orders'), '1']);
        $response->assertSeeInOrder([__('reports.sales_total_revenue'), '500']);
    }

    public function test_average_order_value_divides_revenue_by_completed_orders_only(): void
    {
        $admin = $this->makeAdmin();
        $this->makeOrder(['total' => 300, 'status' => 'delivered']);
        $this->makeOrder(['total' => 700, 'status' => 'delivered']);
        $this->makeOrder(['total' => 5000, 'status' => 'cancelled']);

        $response = $this->actingAs($admin)->get(route('admin.reports.sales'));

        $response->assertOk();
        // (300 + 700) / 2 completed orders = 500 — the cancelled order's
        // total must not dilute or inflate this average.
        $response->assertSeeInOrder([__('reports.sales_average_order_value'), '500']);
    }

    // ---------------------------------------------------------------
    // CSV export
    // ---------------------------------------------------------------

    public function test_csv_export_contains_the_orders_in_range(): void
    {
        $admin = $this->makeAdmin();
        $order = $this->makeOrder(['order_number' => 'ORD-EXPORT-TEST', 'total' => 500]);

        $response = $this->actingAs($admin)->get(route('admin.reports.sales.export'));

        $response->assertOk();
        $this->assertStringStartsWith('text/csv', $response->headers->get('content-type'));
        $content = $response->streamedContent();
        $this->assertStringContainsString('ORD-EXPORT-TEST', $content);
        $this->assertStringContainsString(__('reports.export_order_number'), $content);
    }

    public function test_csv_export_is_forbidden_without_the_permission(): void
    {
        $employee = $this->makeEmployee();

        $this->actingAs($employee)->get(route('admin.reports.sales.export'))->assertForbidden();
    }

    // ---------------------------------------------------------------
    // Cairo day-boundary regression (2026-07 timezone audit)
    // ---------------------------------------------------------------

    /**
     * Reproduces the exact scenario from the audit: an order placed at
     * 00:30 Cairo time is stored as 21:30 UTC the PREVIOUS calendar day
     * (Cairo observes DST/+3 in July). A report for the Cairo calendar
     * day it was actually placed on must include it — before the
     * BusinessDay fix, the UTC-anchored range would have missed it
     * entirely (it would only show up under "yesterday").
     */
    public function test_an_order_placed_just_after_cairo_midnight_is_attributed_to_the_correct_cairo_day(): void
    {
        $admin = $this->makeAdmin();

        // Cairo 2026-07-16 00:30:00 (+03:00 DST) == UTC 2026-07-15 21:30:00.
        $order = $this->makeOrder(['order_number' => 'ORD-CAIRO-MIDNIGHT', 'total' => 500]);
        $order->forceFill(['created_at' => '2026-07-15 21:30:00'])->save();

        // An admin asking for the Cairo calendar day "2026-07-16" should
        // see this order — it's the whole first half-hour of that day.
        $response = $this->actingAs($admin)->get(route('admin.reports.sales', [
            'from' => '2026-07-16', 'to' => '2026-07-16',
        ]));

        $response->assertOk();
        $this->assertSame(1, $response->viewData('totalOrders'));

        // And it must NOT leak into the previous Cairo day's report.
        $previousDay = $this->actingAs($admin)->get(route('admin.reports.sales', [
            'from' => '2026-07-15', 'to' => '2026-07-15',
        ]));
        $this->assertSame(0, $previousDay->viewData('totalOrders'));
    }

    public function test_the_daily_breakdown_buckets_by_cairo_calendar_day_not_utc(): void
    {
        $admin = $this->makeAdmin();

        $order = $this->makeOrder(['total' => 500]);
        $order->forceFill(['created_at' => '2026-07-15 21:30:00'])->save();

        $response = $this->actingAs($admin)->get(route('admin.reports.sales', [
            'from' => '2026-07-16', 'to' => '2026-07-16',
        ]));

        $response->assertOk();
        $daily = $response->viewData('daily');
        $this->assertCount(1, $daily);
        $this->assertSame('2026-07-16', $daily->first()->day);
    }
}
