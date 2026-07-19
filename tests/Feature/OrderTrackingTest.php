<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function makeOrder(array $overrides = []): Order
    {
        $category = Category::create([
            'name_ar' => 'فئة', 'name_en' => 'Category', 'slug' => 'cat-'.uniqid(),
            'is_active' => true, 'sort_order' => 1,
        ]);

        // name_ar/name_en deliberately identical — this suite is about
        // tracker/status logic, not AR/EN rendering, and the default
        // storefront locale is Arabic, so trans_field() resolves name_ar.
        $product = Product::create([
            'category_id' => $category->id, 'name_ar' => 'Product', 'name_en' => 'Product',
            'slug' => 'product-'.uniqid(), 'price' => 500, 'is_active' => true, 'is_featured' => false,
        ]);
        $product->sizes()->create(['size' => 'M', 'stock' => 5]);

        $order = Order::create(array_merge([
            'order_number' => 'ORD-'.uniqid(),
            'customer_name' => 'Test Customer', 'customer_email' => 'customer@example.com', 'customer_phone' => '01000000000',
            'governorate' => 'Cairo', 'city' => 'Nasr City', 'address' => 'Street 1',
            'locale' => 'en', 'subtotal' => 500, 'shipping_fee' => 50, 'discount_amount' => 0, 'total' => 550,
            'status' => 'pending', 'payment_method' => Order::PAYMENT_METHOD_COD,
        ], $overrides));

        $order->items()->create(['product_id' => $product->id, 'product_name' => 'Product', 'size' => 'M', 'price' => 500, 'quantity' => 1]);

        OrderStatusHistory::create(['order_id' => $order->id, 'status' => 'pending', 'note' => 'Order placed.']);

        return $order;
    }

    protected function pushStatus(Order $order, string $status): void
    {
        $order->update(['status' => $status]);
        OrderStatusHistory::create(['order_id' => $order->id, 'status' => $status]);
    }

    // ---------------------------------------------------------------
    // Logged-in customer access
    // ---------------------------------------------------------------

    public function test_logged_in_customer_can_view_their_own_order_tracker(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('account.orders.track', $order));

        $response->assertOk();
        $response->assertSee($order->order_number);
        $response->assertSee(__('orders.status_pending'));
    }

    public function test_logged_in_customer_cannot_view_another_customers_order(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $order = $this->makeOrder(['user_id' => $owner->id]);

        $response = $this->actingAs($intruder)->get(route('account.orders.track', $order));

        $response->assertForbidden();
    }

    public function test_guest_cannot_view_the_account_tracker_route_at_all(): void
    {
        $order = $this->makeOrder();

        $response = $this->get(route('account.orders.track', $order));

        $response->assertRedirect(route('login'));
    }

    // ---------------------------------------------------------------
    // Guest lookup
    // ---------------------------------------------------------------

    public function test_guest_lookup_succeeds_with_matching_order_number_and_email(): void
    {
        $order = $this->makeOrder(['customer_email' => 'layla@example.com', 'customer_phone' => '01099998888']);

        $response = $this->post(route('track-order.lookup'), [
            'order_number' => $order->order_number,
            'contact' => 'layla@example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();

        $trackerResponse = $this->get($response->headers->get('Location'));
        $trackerResponse->assertOk();
        $trackerResponse->assertSee($order->order_number);
    }

    public function test_guest_lookup_succeeds_with_matching_order_number_and_phone(): void
    {
        $order = $this->makeOrder(['customer_email' => 'layla@example.com', 'customer_phone' => '01099998888']);

        $response = $this->post(route('track-order.lookup'), [
            'order_number' => $order->order_number,
            'contact' => '01099998888',
        ]);

        $response->assertRedirect();
        $trackerResponse = $this->get($response->headers->get('Location'));
        $trackerResponse->assertOk();
        $trackerResponse->assertSee($order->order_number);
    }

    public function test_guest_lookup_fails_with_wrong_order_number(): void
    {
        $order = $this->makeOrder(['customer_email' => 'layla@example.com']);

        $response = $this->post(route('track-order.lookup'), [
            'order_number' => 'ORD-DOES-NOT-EXIST',
            'contact' => 'layla@example.com',
        ]);

        $response->assertRedirect(route('track-order.form'));
        $response->assertSessionHas('error', __('orders.track_not_found'));
    }

    public function test_guest_lookup_fails_with_wrong_contact_info_without_revealing_which_part_was_wrong(): void
    {
        $order = $this->makeOrder(['customer_email' => 'layla@example.com', 'customer_phone' => '01099998888']);

        $wrongContact = $this->post(route('track-order.lookup'), [
            'order_number' => $order->order_number,
            'contact' => 'someone-else@example.com',
        ]);

        $wrongOrderNumber = $this->post(route('track-order.lookup'), [
            'order_number' => 'ORD-DOES-NOT-EXIST',
            'contact' => 'layla@example.com',
        ]);

        // Identical generic message either way — nothing distinguishes
        // "right order number, wrong contact" from "wrong order number" for
        // whoever is making the request, so brute-forcing can't learn
        // which order numbers are real.
        $wrongContact->assertSessionHas('error', __('orders.track_not_found'));
        $wrongOrderNumber->assertSessionHas('error', __('orders.track_not_found'));
    }

    public function test_guest_cannot_reach_the_tracker_via_order_number_alone_without_a_valid_signature(): void
    {
        $order = $this->makeOrder();

        // The route exists, but hitting it directly (no signature query
        // string minted by lookup()) must not work — guessing/receiving an
        // order number alone is not enough to see its status.
        $response = $this->get(route('track-order.show', $order, absolute: false));

        $response->assertForbidden();
    }

    /**
     * Regression guard: the guest tracking link must expire, matching the
     * invoice-download precedent (90 days) — this page shows the customer's
     * shipping address and phone, so a signature that never expires would
     * leak that PII indefinitely if the link is forwarded or cached.
     */
    public function test_guest_tracking_link_expires_after_90_days(): void
    {
        $order = $this->makeOrder(['customer_email' => 'layla@example.com']);

        $response = $this->post(route('track-order.lookup'), [
            'order_number' => $order->order_number,
            'contact' => 'layla@example.com',
        ]);
        $trackingUrl = $response->headers->get('Location');

        $this->travel(91)->days();

        $this->get($trackingUrl)->assertForbidden();
    }

    public function test_guest_tracking_link_still_works_within_90_days(): void
    {
        $order = $this->makeOrder(['customer_email' => 'layla@example.com']);

        $response = $this->post(route('track-order.lookup'), [
            'order_number' => $order->order_number,
            'contact' => 'layla@example.com',
        ]);
        $trackingUrl = $response->headers->get('Location');

        $this->travel(89)->days();

        $this->get($trackingUrl)->assertOk();
    }

    public function test_guest_lookup_is_rate_limited(): void
    {
        $order = $this->makeOrder(['customer_email' => 'layla@example.com']);

        for ($i = 0; $i < 10; $i++) {
            $this->post(route('track-order.lookup'), [
                'order_number' => $order->order_number,
                'contact' => 'layla@example.com',
            ]);
        }

        $response = $this->post(route('track-order.lookup'), [
            'order_number' => $order->order_number,
            'contact' => 'layla@example.com',
        ]);

        $response->assertStatus(429);
    }

    // ---------------------------------------------------------------
    // Timeline stages
    // ---------------------------------------------------------------

    public function test_cancelled_order_shows_the_distinct_cancelled_state_not_a_normal_progress_line(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder(['user_id' => $user->id]);
        $this->pushStatus($order, 'cancelled');

        $response = $this->actingAs($user)->get(route('account.orders.track', $order));

        $response->assertOk();
        $response->assertSee(__('orders.status_cancelled'));
        // The shared <style> block's .dj-tracker-steps rule always renders
        // regardless of branch (inline CSS, defined once per page) — only
        // the actual element must be absent for a cancelled order.
        $response->assertDontSee('<div class="dj-tracker-steps"', false);
    }

    public function test_all_real_admin_statuses_render_as_the_correct_timeline_stage(): void
    {
        $user = User::factory()->create();

        foreach (['pending', 'processing', 'shipped', 'delivered'] as $status) {
            $order = $this->makeOrder(['user_id' => $user->id]);
            if ($status !== 'pending') {
                $this->pushStatus($order, $status);
            }

            $response = $this->actingAs($user)->get(route('account.orders.track', $order));

            $response->assertOk();
            $response->assertSee('dj-tracker-steps', false);
            $response->assertSee(__('orders.status_'.$status));
            // The current stage's icon circle carries dj-t-current.
            $response->assertSee('dj-t-current', false);
        }
    }

    public function test_reached_stage_shows_its_timestamp_future_stage_does_not(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder(['user_id' => $user->id]);
        $this->pushStatus($order, 'processing');

        $response = $this->actingAs($user)->get(route('account.orders.track', $order));

        $response->assertOk();
        // "delivered" has never been reached yet.
        $response->assertSeeInOrder([__('orders.status_delivered'), __('orders.track_awaiting')]);
    }

    // ---------------------------------------------------------------
    // Order summary content
    // ---------------------------------------------------------------

    public function test_tracker_shows_order_items_and_totals(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('account.orders.track', $order));

        $response->assertOk();
        $response->assertSee('Product');
        $response->assertSee(number_format($order->total).' EGP');
        $response->assertSee($order->address);
    }
}
