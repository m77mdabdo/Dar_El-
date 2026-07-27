<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\OrderChangeRequest;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Admin usability audit finding #4: the dashboard opened with 24 stat
 * cards + 8 charts before any actionable list, identically for every
 * role, and pending order-change/return requests had zero presence
 * anywhere on it (flagged twice in the original audit). This covers
 * the new "Needs Attention" section's data (including the two open
 * findings it resolves) and the role-based gating of both it and the
 * analytics block.
 */
class DashboardNeedsAttentionTest extends TestCase
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
            'subtotal' => 500, 'shipping_fee' => 0, 'total' => 500, 'status' => 'pending', 'payment_method' => 'cod',
        ], $overrides));
    }

    protected function makeProduct(int $stock): Product
    {
        $category = Category::create(['name_ar' => 'ف', 'name_en' => 'Cat', 'slug' => 'cat-'.uniqid(), 'is_active' => true, 'sort_order' => 1]);
        $product = Product::create(['category_id' => $category->id, 'name_ar' => 'م', 'name_en' => 'Prod', 'slug' => 'prod-'.uniqid(), 'price' => 300, 'is_active' => true, 'is_featured' => false]);
        $product->sizes()->create(['size' => 'M', 'stock' => $stock]);

        return $product;
    }

    // ---------------------------------------------------------------
    // Data correctness
    // ---------------------------------------------------------------

    public function test_needs_attention_counts_pending_orders_only(): void
    {
        $admin = $this->makeAdmin();
        $this->makeOrder(['status' => 'pending']);
        $this->makeOrder(['status' => 'pending']);
        $this->makeOrder(['status' => 'delivered']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSeeInOrder([__('admin.dashboard.attention_pending_orders'), '2']);
    }

    /**
     * The finding flagged twice in the original audit: pending order-
     * change/return requests had no stat card and no notification
     * anywhere. This proves the count is both correct (pending only,
     * not contacted/resolved) and actually rendered on the dashboard.
     */
    public function test_needs_attention_counts_pending_order_change_requests_only(): void
    {
        $admin = $this->makeAdmin();
        $order = $this->makeOrder();
        OrderChangeRequest::create(['order_id' => $order->id, 'type' => 'cancel', 'reason' => 'changed_mind', 'status' => 'pending']);
        OrderChangeRequest::create(['order_id' => $order->id, 'type' => 'modify', 'reason' => 'wrong_size', 'status' => 'contacted']);
        $resolvedOrder = $this->makeOrder();
        OrderChangeRequest::create(['order_id' => $resolvedOrder->id, 'type' => 'cancel', 'reason' => 'other', 'status' => 'resolved']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSeeInOrder([__('admin.dashboard.attention_change_requests'), '1']);
        $response->assertSee(route('admin.order-change-requests.index'), false);
    }

    public function test_needs_attention_stock_alerts_combines_low_and_out_of_stock(): void
    {
        $admin = $this->makeAdmin();
        $this->makeProduct(2); // low stock (<= threshold of 5, > 0)
        $this->makeProduct(0); // out of stock
        $this->makeProduct(50); // healthy, excluded

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSeeInOrder([__('admin.dashboard.attention_stock_alerts'), '2']);
    }

    public function test_needs_attention_counts_unread_messages_only(): void
    {
        $admin = $this->makeAdmin();
        ContactMessage::create(['name' => 'A', 'email' => 'a@example.com', 'message' => 'Hi', 'is_read' => false]);
        ContactMessage::create(['name' => 'B', 'email' => 'b@example.com', 'message' => 'Hi', 'is_read' => true]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSeeInOrder([__('admin.dashboard.attention_unread_messages'), '1']);
    }

    public function test_needs_attention_counts_pending_reviews_only(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct(20);
        Review::create(['product_id' => $product->id, 'name' => 'X', 'rating' => 4, 'comment' => 'A sufficiently long comment.', 'status' => 'pending']);
        Review::create(['product_id' => $product->id, 'name' => 'Y', 'rating' => 5, 'comment' => 'Another sufficiently long comment.', 'status' => 'approved']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSeeInOrder([__('admin.dashboard.attention_pending_reviews'), '1']);
    }

    // ---------------------------------------------------------------
    // Role-based simplification
    // ---------------------------------------------------------------

    public function test_admin_sees_the_full_dashboard(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee(__('admin.dashboard.attention_title'));
        $response->assertSee(__('admin.dashboard.analytics_title'));
        $response->assertSee('dj-admin-chart', false);
        $response->assertSee(__('admin.dashboard.recent_orders'));
    }

    public function test_employee_with_zero_permissions_sees_neither_attention_nor_analytics(): void
    {
        $employee = $this->makeEmployee();

        $response = $this->actingAs($employee)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertDontSee(__('admin.dashboard.attention_title'));
        $response->assertDontSee(__('admin.dashboard.analytics_title'));
        $response->assertDontSee('dj-admin-chart', false);
        $response->assertSee(__('admin.dashboard.attention_empty_permissions'));
    }

    public function test_employee_with_only_orders_view_sees_relevant_attention_cards_and_recent_orders_but_not_analytics(): void
    {
        $employee = $this->makeEmployee();
        $this->grant($employee, 'orders.view');
        $order = $this->makeOrder();

        $response = $this->actingAs($employee)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee(__('admin.dashboard.attention_title'));
        $response->assertSee(__('admin.dashboard.attention_pending_orders'));
        $response->assertSee(__('admin.dashboard.attention_change_requests'));
        $response->assertDontSee(__('admin.dashboard.attention_stock_alerts'));
        $response->assertDontSee(__('admin.dashboard.attention_unread_messages'));
        $response->assertDontSee(__('admin.dashboard.attention_pending_reviews'));
        $response->assertDontSee(__('admin.dashboard.analytics_title'));
        $response->assertDontSee('dj-admin-chart', false);
        $response->assertSee(__('admin.dashboard.recent_orders'));
        $response->assertSee($order->order_number);
        $response->assertDontSee(__('admin.dashboard.recent_messages'));
    }

    public function test_employee_with_only_inventory_view_sees_only_stock_alerts_and_low_stock_list(): void
    {
        $employee = $this->makeEmployee();
        $this->grant($employee, 'inventory.view');
        $this->makeProduct(1);

        $response = $this->actingAs($employee)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee(__('admin.dashboard.attention_stock_alerts'));
        $response->assertDontSee(__('admin.dashboard.attention_pending_orders'));
        $response->assertDontSee(__('admin.dashboard.attention_unread_messages'));
        $response->assertDontSee(__('admin.dashboard.attention_pending_reviews'));
        $response->assertDontSee(__('admin.dashboard.analytics_title'));
        $response->assertSee(__('admin.dashboard.low_stock_section'));
        $response->assertDontSee(__('admin.dashboard.recent_orders'));
    }

    public function test_employee_with_only_reports_view_sees_analytics_but_no_attention_section(): void
    {
        $employee = $this->makeEmployee();
        $this->grant($employee, 'reports.view');

        $response = $this->actingAs($employee)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee(__('admin.dashboard.analytics_title'));
        $response->assertSee('dj-admin-chart', false);
        $response->assertDontSee(__('admin.dashboard.attention_title'));
        $response->assertDontSee(__('admin.dashboard.attention_empty_permissions'));
    }

    /**
     * The critical regression this redesign could introduce: the whole
     * payload (needsAttention included) sits behind one shared 60s
     * cache key. If per-role filtering happened INSIDE that cached
     * closure, whichever role's request happened to populate the cache
     * would leak its view to every other role for the next 60s. This
     * proves filtering happens after the cache read, per request.
     */
    public function test_cache_populated_by_an_admin_request_does_not_leak_admin_only_sections_to_a_later_employee_request(): void
    {
        $admin = $this->makeAdmin();
        $employee = $this->makeEmployee();
        $this->grant($employee, 'inventory.view');

        // Admin visits first — warms the shared 60s cache.
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();

        // Employee visits within that same cache window.
        $response = $this->actingAs($employee)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertDontSee(__('admin.dashboard.analytics_title'));
        $response->assertDontSee('dj-admin-chart', false);
        $response->assertDontSee(__('admin.dashboard.attention_pending_orders'));
        $response->assertDontSee(__('admin.dashboard.recent_orders'));
        $response->assertSee(__('admin.dashboard.attention_stock_alerts'));
    }
}
