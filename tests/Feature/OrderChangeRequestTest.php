<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderChangeRequest;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrderChangeRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function makeAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        return $admin;
    }

    protected function makeOrder(array $overrides = [], ?User $user = null): Order
    {
        $category = Category::create([
            'name_ar' => 'فئة', 'name_en' => 'Category', 'slug' => 'cat-'.uniqid(),
            'is_active' => true, 'sort_order' => 1,
        ]);
        $product = Product::create([
            'category_id' => $category->id, 'name_ar' => 'منتج', 'name_en' => 'Product',
            'slug' => 'product-'.uniqid(), 'price' => 500, 'is_active' => true, 'is_featured' => false,
        ]);

        $order = Order::create(array_merge([
            'order_number' => 'ORD-'.uniqid(),
            'user_id' => $user?->id,
            'customer_name' => 'Test Customer', 'customer_email' => 'customer@example.com', 'customer_phone' => '01000000000',
            'governorate' => 'Cairo', 'city' => 'Nasr City', 'address' => 'Street 1',
            'subtotal' => 500, 'shipping_fee' => 0, 'total' => 500,
            'status' => 'pending', 'payment_method' => Order::PAYMENT_METHOD_COD,
        ], $overrides));

        $order->items()->create([
            'product_id' => $product->id, 'product_name' => 'Product', 'size' => 'M', 'price' => 500, 'quantity' => 1,
        ]);

        return $order;
    }

    protected function markDelivered(Order $order, ?\Illuminate\Support\Carbon $at = null): void
    {
        $order->update(['status' => 'delivered']);
        $history = OrderStatusHistory::create(['order_id' => $order->id, 'status' => 'delivered']);
        $history->forceFill(['created_at' => $at ?? now()])->save();
    }

    protected function payload(array $overrides = []): array
    {
        return array_merge([
            'type' => 'modify',
            'reason' => 'wrong_size',
        ], $overrides);
    }

    // ---------------------------------------------------------------
    // Ownership / access
    // ---------------------------------------------------------------

    public function test_authenticated_owner_can_submit_a_request_for_their_pending_order(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder(user: $user);

        $response = $this->actingAs($user)->postJson(route('order-change-requests.store', $order), $this->payload());

        $response->assertOk();
        $this->assertDatabaseHas('order_change_requests', ['order_id' => $order->id, 'type' => 'modify', 'reason' => 'wrong_size']);
    }

    public function test_authenticated_user_cannot_submit_for_someone_elses_order(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $order = $this->makeOrder(user: $owner);

        $response = $this->actingAs($other)->postJson(route('order-change-requests.store', $order), $this->payload());

        $response->assertForbidden();
        $this->assertDatabaseCount('order_change_requests', 0);
    }

    public function test_guest_cannot_submit_without_a_valid_signature(): void
    {
        $order = $this->makeOrder();

        $response = $this->postJson(route('order-change-requests.store', $order), $this->payload());

        $response->assertForbidden();
        $this->assertDatabaseCount('order_change_requests', 0);
    }

    public function test_guest_can_submit_via_a_valid_signed_url(): void
    {
        $order = $this->makeOrder();
        $signedUrl = URL::temporarySignedRoute('order-change-requests.store', now()->addDays(90), ['order' => $order->id]);

        $response = $this->postJson($signedUrl, $this->payload());

        $response->assertOk();
        $this->assertDatabaseHas('order_change_requests', ['order_id' => $order->id]);
    }

    // ---------------------------------------------------------------
    // Window gating
    // ---------------------------------------------------------------

    public function test_request_is_rejected_once_order_is_processing(): void
    {
        $order = $this->makeOrder(['status' => 'processing']);
        $signedUrl = URL::temporarySignedRoute('order-change-requests.store', now()->addDays(90), ['order' => $order->id]);

        $response = $this->postJson($signedUrl, $this->payload());

        $response->assertStatus(422);
        $this->assertDatabaseCount('order_change_requests', 0);
    }

    public function test_pending_window_rejects_exchange_or_return_type(): void
    {
        $order = $this->makeOrder(['status' => 'pending']);
        $signedUrl = URL::temporarySignedRoute('order-change-requests.store', now()->addDays(90), ['order' => $order->id]);

        $response = $this->postJson($signedUrl, $this->payload(['type' => 'exchange']));

        $response->assertStatus(422);
    }

    public function test_delivered_window_rejects_modify_or_cancel_type(): void
    {
        $order = $this->makeOrder();
        $this->markDelivered($order);
        $signedUrl = URL::temporarySignedRoute('order-change-requests.store', now()->addDays(90), ['order' => $order->id]);

        $response = $this->postJson($signedUrl, $this->payload(['type' => 'modify']));

        $response->assertStatus(422);
    }

    public function test_exchange_is_allowed_within_3_days_of_the_real_delivered_timestamp(): void
    {
        $order = $this->makeOrder();
        $this->markDelivered($order, now()->subDays(2));
        $signedUrl = URL::temporarySignedRoute('order-change-requests.store', now()->addDays(90), ['order' => $order->id]);

        $response = $this->postJson($signedUrl, $this->payload(['type' => 'exchange']));

        $response->assertOk();
    }

    public function test_exchange_is_rejected_after_3_days_of_the_real_delivered_timestamp(): void
    {
        $order = $this->makeOrder();
        $this->markDelivered($order, now()->subDays(4));
        $signedUrl = URL::temporarySignedRoute('order-change-requests.store', now()->addDays(90), ['order' => $order->id]);

        $response = $this->postJson($signedUrl, $this->payload(['type' => 'return']));

        $response->assertStatus(422);
        $this->assertDatabaseCount('order_change_requests', 0);
    }

    /**
     * Regression guard for the exact bug this feature was built to avoid:
     * updated_at (e.g. an admin editing the shipping address after delivery)
     * must never be mistaken for the real delivered timestamp — only a real
     * statusHistories row for 'delivered' counts.
     */
    public function test_updated_at_is_never_used_as_a_stand_in_for_the_real_delivered_timestamp(): void
    {
        $order = $this->makeOrder();
        $this->markDelivered($order, now()->subDays(4));
        // Touch the order well after the real delivery — if deliveredAt()
        // ever fell back to updated_at, this would incorrectly reopen the window.
        $order->forceFill(['updated_at' => now()])->save();

        $signedUrl = URL::temporarySignedRoute('order-change-requests.store', now()->addDays(90), ['order' => $order->id]);
        $response = $this->postJson($signedUrl, $this->payload(['type' => 'exchange']));

        $response->assertStatus(422);
    }

    public function test_cancelled_order_has_no_open_window(): void
    {
        $order = $this->makeOrder(['status' => 'cancelled']);
        $signedUrl = URL::temporarySignedRoute('order-change-requests.store', now()->addDays(90), ['order' => $order->id]);

        $response = $this->postJson($signedUrl, $this->payload());

        $response->assertStatus(422);
    }

    // ---------------------------------------------------------------
    // Validation
    // ---------------------------------------------------------------

    public function test_reason_must_be_one_of_the_known_options(): void
    {
        $order = $this->makeOrder();
        $signedUrl = URL::temporarySignedRoute('order-change-requests.store', now()->addDays(90), ['order' => $order->id]);

        $response = $this->postJson($signedUrl, $this->payload(['reason' => 'not-a-real-reason']));

        $response->assertStatus(422);
    }

    public function test_item_ids_must_belong_to_the_order(): void
    {
        $order = $this->makeOrder();
        $otherOrder = $this->makeOrder();
        $foreignItemId = $otherOrder->items->first()->id;

        $signedUrl = URL::temporarySignedRoute('order-change-requests.store', now()->addDays(90), ['order' => $order->id]);
        $response = $this->postJson($signedUrl, $this->payload(['order_item_ids' => [$foreignItemId]]));

        $response->assertStatus(422);
    }

    public function test_valid_item_ids_are_stored(): void
    {
        $order = $this->makeOrder();
        $itemId = $order->items->first()->id;

        $signedUrl = URL::temporarySignedRoute('order-change-requests.store', now()->addDays(90), ['order' => $order->id]);
        $response = $this->postJson($signedUrl, $this->payload(['order_item_ids' => [$itemId]]));

        $response->assertOk();
        $this->assertSame([$itemId], OrderChangeRequest::first()->order_item_ids);
    }

    // ---------------------------------------------------------------
    // Duplicate guard + rate limiting
    // ---------------------------------------------------------------

    public function test_a_second_request_is_rejected_while_one_is_already_pending(): void
    {
        $order = $this->makeOrder();
        $signedUrl = URL::temporarySignedRoute('order-change-requests.store', now()->addDays(90), ['order' => $order->id]);

        $this->postJson($signedUrl, $this->payload())->assertOk();
        $second = $this->postJson($signedUrl, $this->payload());

        $second->assertStatus(422);
        $this->assertDatabaseCount('order_change_requests', 1);
    }

    /**
     * The controller-level test above proves the ordinary sequential case
     * works. This one proves the actual security property: the guard is a
     * database constraint, not an app-level check-then-create (which is a
     * TOCTOU race — two concurrent requests can both see "no pending row"
     * and both insert before either one commits). Creating two pending rows
     * directly through the model, back to back with no controller/HTTP
     * layer involved at all, still can't succeed — proving the invariant
     * holds regardless of request timing, not just in the single-threaded
     * order a test happens to run in.
     */
    public function test_the_database_itself_rejects_a_second_pending_row_for_the_same_order(): void
    {
        $order = $this->makeOrder();
        OrderChangeRequest::create(['order_id' => $order->id, 'type' => 'modify', 'reason' => 'wrong_size']);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        OrderChangeRequest::create(['order_id' => $order->id, 'type' => 'cancel', 'reason' => 'changed_mind']);
    }

    public function test_a_new_pending_request_is_allowed_once_the_previous_one_is_resolved(): void
    {
        $order = $this->makeOrder();
        $signedUrl = URL::temporarySignedRoute('order-change-requests.store', now()->addDays(90), ['order' => $order->id]);

        $this->postJson($signedUrl, $this->payload())->assertOk();

        OrderChangeRequest::where('order_id', $order->id)->first()->update(['status' => 'resolved']);

        $second = $this->postJson($signedUrl, $this->payload());

        $second->assertOk();
        $this->assertDatabaseCount('order_change_requests', 2);
    }

    public function test_submission_is_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $order = $this->makeOrder();
            $signedUrl = URL::temporarySignedRoute('order-change-requests.store', now()->addDays(90), ['order' => $order->id]);
            $this->postJson($signedUrl, $this->payload());
        }

        $order = $this->makeOrder();
        $signedUrl = URL::temporarySignedRoute('order-change-requests.store', now()->addDays(90), ['order' => $order->id]);
        $response = $this->postJson($signedUrl, $this->payload());

        $response->assertStatus(429);
    }

    // ---------------------------------------------------------------
    // WhatsApp message
    // ---------------------------------------------------------------

    public function test_successful_submission_returns_a_whatsapp_url_when_configured(): void
    {
        Setting::set('whatsapp_number', '+201234567890');
        $order = $this->makeOrder();
        $signedUrl = URL::temporarySignedRoute('order-change-requests.store', now()->addDays(90), ['order' => $order->id]);

        $response = $this->postJson($signedUrl, $this->payload(['notes' => 'Please call me']));

        $response->assertOk();
        $url = $response->json('whatsapp_url');
        $this->assertStringStartsWith('https://wa.me/201234567890?text=', $url);

        $decoded = urldecode(explode('text=', $url)[1]);
        $this->assertStringContainsString($order->order_number, $decoded);
        $this->assertStringContainsString($order->customer_name, $decoded);
        $this->assertStringContainsString('Please call me', $decoded);
    }

    public function test_whatsapp_url_is_null_when_not_configured(): void
    {
        Setting::set('whatsapp_number', null);
        $order = $this->makeOrder();
        $signedUrl = URL::temporarySignedRoute('order-change-requests.store', now()->addDays(90), ['order' => $order->id]);

        $response = $this->postJson($signedUrl, $this->payload());

        $response->assertOk();
        $this->assertNull($response->json('whatsapp_url'));
    }

    // ---------------------------------------------------------------
    // Admin
    // ---------------------------------------------------------------

    public function test_admin_can_view_the_change_requests_list(): void
    {
        $admin = $this->makeAdmin();
        $order = $this->makeOrder();
        OrderChangeRequest::create(['order_id' => $order->id, 'type' => 'modify', 'reason' => 'wrong_size']);

        $response = $this->actingAs($admin)->get(route('admin.order-change-requests.index'));

        $response->assertOk();
        $response->assertSee($order->order_number);
    }

    public function test_guest_is_redirected_to_login_from_the_admin_list(): void
    {
        $response = $this->get(route('admin.order-change-requests.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_update_a_requests_status(): void
    {
        $admin = $this->makeAdmin();
        $order = $this->makeOrder();
        $changeRequest = OrderChangeRequest::create(['order_id' => $order->id, 'type' => 'modify', 'reason' => 'wrong_size']);

        $response = $this->actingAs($admin)->patch(route('admin.order-change-requests.status', $changeRequest), ['status' => 'resolved']);

        $response->assertRedirect();
        $this->assertSame('resolved', $changeRequest->fresh()->status);
    }
}
