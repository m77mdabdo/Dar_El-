<?php

namespace Tests\Feature\Admin;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\User;
use App\Notifications\OrderCancelled;
use App\Notifications\OrderStatusUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrderManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function makeAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        return $admin;
    }

    /**
     * A cancellable order with two items, each a different product/size,
     * with stock_deducted_at already set (simulating a real checkout) and
     * stock_restored_at null — restoreStock() is eligible to run.
     *
     * @return array{0: Order, 1: ProductSize, 2: ProductSize} order, sizeA, sizeB
     */
    protected function makeCancellableOrderWithTwoItems(int $stockA = 3, int $stockB = 5): array
    {
        $category = Category::create([
            'name_ar' => 'فئة', 'name_en' => 'Category', 'slug' => 'cat-'.uniqid(),
            'is_active' => true, 'sort_order' => 1,
        ]);

        $productA = Product::create([
            'category_id' => $category->id, 'name_ar' => 'منتج أ', 'name_en' => 'Product A',
            'slug' => 'product-a-'.uniqid(), 'price' => 100, 'is_active' => true, 'is_featured' => false,
        ]);
        $sizeA = $productA->sizes()->create(['size' => 'M', 'stock' => $stockA]);

        $productB = Product::create([
            'category_id' => $category->id, 'name_ar' => 'منتج ب', 'name_en' => 'Product B',
            'slug' => 'product-b-'.uniqid(), 'price' => 150, 'is_active' => true, 'is_featured' => false,
        ]);
        $sizeB = $productB->sizes()->create(['size' => 'L', 'stock' => $stockB]);

        $order = Order::create([
            'order_number' => 'ORD-'.uniqid(),
            'customer_name' => 'Test Customer', 'customer_email' => 'customer@example.com', 'customer_phone' => '01000000000',
            'governorate' => 'Cairo', 'city' => 'Nasr City', 'address' => 'Street 1',
            'locale' => 'en', 'subtotal' => 250, 'shipping_fee' => 0, 'discount_amount' => 0, 'total' => 250,
            'status' => 'processing', 'payment_method' => Order::PAYMENT_METHOD_COD,
            'stock_deducted_at' => now(),
        ]);

        $order->items()->create(['product_id' => $productA->id, 'product_name' => 'Product A', 'size' => 'M', 'price' => 100, 'quantity' => 2]);
        $order->items()->create(['product_id' => $productB->id, 'product_name' => 'Product B', 'size' => 'L', 'price' => 150, 'quantity' => 1]);

        return [$order, $sizeA, $sizeB];
    }

    /**
     * SCENARIO B (item 9): a restoreStock() failure mid-loop must roll back
     * the ENTIRE transaction — status change, history, activity log, and
     * both items' stock — leaving the order exactly as it was before the
     * request, not in a state that says "cancelled" while stock was never
     * actually restored.
     *
     * Forces a failure on the second item's stock increment via
     * ProductSize::updating() (confirmed empirically that increment() DOES
     * fire this event in this Laravel version), simulating a genuine
     * mid-loop failure (e.g. a transient DB error) rather than a
     * validation/business-logic rejection.
     *
     * BASELINE (pre-fix, confirmed before implementing anything): the
     * stock numbers themselves already rolled back correctly (restoreStock()
     * already had its own inner DB::transaction()), but order.status was
     * ALREADY 'cancelled' and OrderStatusHistory/ActivityLog rows already
     * existed — because those ran with no transaction, before
     * restoreStock() was even reached, and committed independently. The
     * admin saw a raw 500, but the order was left permanently claiming
     * "cancelled" while stock was silently never returned.
     */
    public function test_scenario_b_restock_failure_mid_loop_rolls_back_the_entire_transaction(): void
    {
        Notification::fake();
        [$order, $sizeA, $sizeB] = $this->makeCancellableOrderWithTwoItems(stockA: 3, stockB: 5);
        $admin = $this->makeAdmin();

        ProductSize::updating(function ($model) use ($sizeB) {
            if ($model->id === $sizeB->id) {
                throw new \RuntimeException('Simulated failure restoring stock for item 2');
            }
        });

        $response = $this->actingAs($admin)->patch(route('admin.orders.status', $order), [
            'status' => 'cancelled',
        ]);

        $response->assertStatus(500);

        $sizeA->refresh();
        $sizeB->refresh();
        $order->refresh();

        // The whole attempt failed — nothing committed, not even the parts
        // that "succeeded" before the failing item was reached.
        $this->assertSame(3, $sizeA->stock, "Item 1's stock increment was not rolled back even though item 2's failed — the exact bug this fix addresses.");
        $this->assertSame(5, $sizeB->stock);
        $this->assertSame('processing', $order->status, 'Order status shows cancelled even though the cancellation did not actually complete — the exact bug this fix addresses.');
        $this->assertNull($order->stock_restored_at);
        $this->assertSame(0, OrderStatusHistory::where('order_id', $order->id)->count());
        $this->assertSame(0, ActivityLog::where('subject_type', Order::class)->where('subject_id', $order->id)->count());
    }

    /**
     * SCENARIO A (item 9): a notify() failure must NOT roll back a status
     * change that has already legitimately happened — same lesson as item
     * 7 (StockAlertService) and the CartTrackingService fix. Notifications
     * run after the transaction commits, in their own try/catch.
     */
    public function test_scenario_a_status_change_still_succeeds_when_the_customer_notification_throws(): void
    {
        [$order] = $this->makeCancellableOrderWithTwoItems();
        $user = User::factory()->create();
        $order->update(['user_id' => $user->id]);
        $admin = $this->makeAdmin();

        // Status is 'processing' here, not 'cancelled', so the only
        // Notification::send()-routed call this triggers is
        // $order->user->notify(new OrderStatusUpdated($order)) — it
        // resolves the same underlying ChannelManager singleton the
        // Notification facade's accessor points to (confirmed in the item
        // 8 fix), so mocking the facade here intercepts it.
        Notification::shouldReceive('send')->andThrow(new \RuntimeException('Simulated notification transport failure'));

        $response = $this->actingAs($admin)->patch(route('admin.orders.status', $order), [
            'status' => 'processing',
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();

        $order->refresh();
        $this->assertSame('processing', $order->status, 'Status change was rolled back or the request failed when the customer notification failed — the exact bug this fix addresses.');
        $this->assertSame(1, OrderStatusHistory::where('order_id', $order->id)->count());
        $this->assertSame(1, ActivityLog::where('subject_type', Order::class)->where('subject_id', $order->id)->count());
    }

    /**
     * SCENARIO C (item 9): the normal success path — no simulated
     * failures — still works end-to-end exactly as before: status
     * changes, history is recorded, stock is restored, and both the
     * customer and admin notifications are sent.
     */
    public function test_scenario_c_normal_cancellation_succeeds_end_to_end(): void
    {
        Notification::fake();
        [$order, $sizeA, $sizeB] = $this->makeCancellableOrderWithTwoItems(stockA: 3, stockB: 5);
        $user = User::factory()->create();
        $order->update(['user_id' => $user->id]);
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->patch(route('admin.orders.status', $order), [
            'status' => 'cancelled',
            'note' => 'Customer requested cancellation.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $order->refresh();
        $sizeA->refresh();
        $sizeB->refresh();

        $this->assertSame('cancelled', $order->status);
        $this->assertNotNull($order->stock_restored_at);
        $this->assertSame(5, $sizeA->stock, '3 + 2 (item quantity) restored');
        $this->assertSame(6, $sizeB->stock, '5 + 1 (item quantity) restored');

        $history = OrderStatusHistory::where('order_id', $order->id)->first();
        $this->assertNotNull($history);
        $this->assertSame('cancelled', $history->status);
        $this->assertSame('Customer requested cancellation.', $history->note);
        $this->assertSame($admin->id, $history->changed_by);

        $this->assertSame(1, ActivityLog::where('subject_type', Order::class)->where('subject_id', $order->id)->count());

        Notification::assertSentTo($user, OrderStatusUpdated::class);
        Notification::assertSentTo($admin, OrderCancelled::class);
    }
}
