<?php

namespace Tests\Feature;

use App\Models\BackInStockSubscription;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\PushSubscription;
use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PushNotificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * p256dh/auth here are shaped like real Web Push key material (correct
     * base64url charset and length — a real p256dh is a base64url-encoded,
     * uncompressed EC P-256 public key, always ~87 chars; auth is a 16-byte
     * secret, ~22 chars, confirmed against a real browser-generated
     * subscription) so these tests exercise the same validation path real
     * traffic does, not a shortcut around it. See ValidWebPushEndpointTest
     * for the tests specifically targeting format/host rejection.
     */
    protected function subscribePayload(array $overrides = []): array
    {
        return array_merge([
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/'.uniqid(),
            'keys' => [
                'p256dh' => 'BNZt1sr089T8_QclkT-OqDVevOhFACXtStn5mqb2AP6VGhj1YnLwbceJ6PrP-H5xoKzaLr4_DIgud1fiDgSkT'.substr(uniqid(), 0, 2),
                'auth' => 'TXzvF9_PZts9JMuIpVC1'.substr(uniqid(), 0, 2),
            ],
        ], $overrides);
    }

    protected function makeProduct(): Product
    {
        $category = Category::create([
            'name_ar' => 'فئة', 'name_en' => 'Category', 'slug' => 'cat-'.uniqid(),
            'is_active' => true, 'sort_order' => 1,
        ]);

        return Product::create([
            'category_id' => $category->id, 'name_ar' => 'منتج', 'name_en' => 'Product',
            'slug' => 'product-'.uniqid(), 'price' => 500, 'is_active' => true, 'is_featured' => false,
        ]);
    }

    // ---------------------------------------------------------------
    // Subscribe endpoint
    // ---------------------------------------------------------------

    public function test_guest_can_subscribe_to_push_notifications(): void
    {
        $payload = $this->subscribePayload();

        $response = $this->postJson(route('push.subscribe'), $payload);

        $response->assertOk();
        $this->assertDatabaseHas('push_subscriptions', [
            'endpoint' => $payload['endpoint'],
            'p256dh' => $payload['keys']['p256dh'],
            'auth' => $payload['keys']['auth'],
            'user_id' => null,
        ]);
    }

    public function test_authenticated_users_subscription_records_their_user_id(): void
    {
        $user = User::factory()->create();
        $payload = $this->subscribePayload();

        $this->actingAs($user)->postJson(route('push.subscribe'), $payload)->assertOk();

        $this->assertDatabaseHas('push_subscriptions', [
            'endpoint' => $payload['endpoint'],
            'user_id' => $user->id,
        ]);
    }

    public function test_resubscribing_with_the_same_endpoint_updates_the_existing_row_instead_of_duplicating(): void
    {
        $endpoint = 'https://fcm.googleapis.com/fcm/send/'.uniqid();

        $updatedKeys = [
            'p256dh' => 'BAbcdEFghijKLmnoPQRstuvWXyz0123456789_-AbcdEFghijKLmnoPQRstuvWXyz0123456789_-ABCDE',
            'auth' => 'ZYXwvutsRQPOnmlKJihGFE',
        ];

        $this->postJson(route('push.subscribe'), $this->subscribePayload(['endpoint' => $endpoint]))->assertOk();
        $this->postJson(route('push.subscribe'), $this->subscribePayload([
            'endpoint' => $endpoint,
            'keys' => $updatedKeys,
        ]))->assertOk();

        $this->assertSame(1, PushSubscription::where('endpoint', $endpoint)->count());
        $this->assertDatabaseHas('push_subscriptions', ['endpoint' => $endpoint, 'p256dh' => $updatedKeys['p256dh']]);
    }

    public function test_subscribe_rejects_a_payload_missing_required_fields(): void
    {
        $response = $this->postJson(route('push.subscribe'), ['endpoint' => 'not-a-url']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['endpoint', 'keys.p256dh', 'keys.auth']);
    }

    public function test_subscribe_endpoint_is_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson(route('push.subscribe'), $this->subscribePayload())->assertOk();
        }

        $this->postJson(route('push.subscribe'), $this->subscribePayload())->assertStatus(429);
    }

    // ---------------------------------------------------------------
    // link_token: ties a subscribe call to a specific back-in-stock signup
    // ---------------------------------------------------------------

    public function test_a_valid_link_token_attaches_the_subscription_to_its_back_in_stock_signup(): void
    {
        $product = $this->makeProduct();
        $size = $product->sizes()->create(['size' => 'M', 'stock' => 0]);

        $signupResponse = $this->postJson(route('back-in-stock.store', $product), [
            'email' => 'linker@example.com', 'product_size_id' => $size->id,
        ]);
        $signupResponse->assertOk();
        $linkToken = $signupResponse->json('push_link_token');
        $this->assertNotEmpty($linkToken);

        $subscription = BackInStockSubscription::where('email', 'linker@example.com')->firstOrFail();
        $this->assertNull($subscription->push_subscription_id);

        $this->postJson(route('push.subscribe'), $this->subscribePayload(['link_token' => $linkToken]))->assertOk();

        $this->assertNotNull($subscription->fresh()->push_subscription_id);
    }

    public function test_an_invalid_link_token_is_silently_ignored(): void
    {
        $response = $this->postJson(route('push.subscribe'), $this->subscribePayload(['link_token' => 'not-a-real-token']));

        $response->assertOk();
        // No exception, no partial failure — the subscription itself still gets created.
        $this->assertDatabaseCount('push_subscriptions', 1);
    }

    public function test_a_link_token_can_only_be_used_once(): void
    {
        $product = $this->makeProduct();
        $size = $product->sizes()->create(['size' => 'M', 'stock' => 0]);

        $signupResponse = $this->postJson(route('back-in-stock.store', $product), [
            'email' => 'onceonly@example.com', 'product_size_id' => $size->id,
        ]);
        $linkToken = $signupResponse->json('push_link_token');

        $this->postJson(route('push.subscribe'), $this->subscribePayload(['link_token' => $linkToken]))->assertOk();

        // A second, different subscription trying to reuse the same
        // already-consumed token must not steal the link.
        $secondSubscribeResponse = $this->postJson(route('push.subscribe'), $this->subscribePayload(['link_token' => $linkToken]));
        $secondSubscribeResponse->assertOk();

        $subscription = BackInStockSubscription::where('email', 'onceonly@example.com')->firstOrFail();
        $firstPushSubscription = PushSubscription::orderBy('id')->first();
        $this->assertTrue($subscription->push_subscription_id === $firstPushSubscription->id);
    }

    public function test_link_token_does_not_overwrite_an_already_linked_subscription(): void
    {
        $product = $this->makeProduct();
        $size = $product->sizes()->create(['size' => 'M', 'stock' => 0]);

        $subscription = BackInStockSubscription::create([
            'product_id' => $product->id, 'product_size_id' => $size->id, 'email' => 'already-linked@example.com',
        ]);
        $existingPush = PushSubscription::create([
            'endpoint' => 'https://existing.example.com/x', 'p256dh' => 'a', 'auth' => 'b',
        ]);
        $subscription->update(['push_subscription_id' => $existingPush->id]);

        Cache::put('push-link-fake-token', $subscription->id, now()->addMinutes(15));

        $this->postJson(route('push.subscribe'), $this->subscribePayload(['link_token' => 'fake-token']))->assertOk();

        $this->assertSame($existingPush->id, $subscription->fresh()->push_subscription_id);
    }

    // ---------------------------------------------------------------
    // Back-in-stock push wiring
    // ---------------------------------------------------------------

    public function test_back_in_stock_service_sends_a_push_when_a_subscription_is_linked(): void
    {
        $product = $this->makeProduct();
        $size = $product->sizes()->create(['size' => 'M', 'stock' => 0]);

        $pushSubscription = PushSubscription::create([
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/linked', 'p256dh' => 'a', 'auth' => 'b',
        ]);
        BackInStockSubscription::create([
            'product_id' => $product->id, 'product_size_id' => $size->id, 'email' => 'push-watch@example.com',
            'push_subscription_id' => $pushSubscription->id,
        ]);

        $this->mock(PushNotificationService::class, function ($mock) use ($pushSubscription) {
            $mock->shouldReceive('sendToSubscription')
                ->once()
                ->withArgs(fn ($subscription) => $subscription->is($pushSubscription));
        });

        app(\App\Services\BackInStockService::class)->checkAndNotify($product, $size, before: 0, after: 3);
    }

    public function test_back_in_stock_service_does_not_attempt_a_push_when_no_subscription_is_linked(): void
    {
        $product = $this->makeProduct();
        $size = $product->sizes()->create(['size' => 'M', 'stock' => 0]);

        BackInStockSubscription::create([
            'product_id' => $product->id, 'product_size_id' => $size->id, 'email' => 'no-push@example.com',
        ]);

        $this->mock(PushNotificationService::class, function ($mock) {
            $mock->shouldNotReceive('sendToSubscription');
        });

        app(\App\Services\BackInStockService::class)->checkAndNotify($product, $size, before: 0, after: 3);
    }

    // ---------------------------------------------------------------
    // Order-status push wiring
    // ---------------------------------------------------------------

    protected function makeAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        return $admin;
    }

    public function test_order_status_change_sends_a_push_to_the_orders_user(): void
    {
        $admin = $this->makeAdmin();
        $customer = User::factory()->create();
        $order = Order::create([
            'order_number' => 'ORD-'.uniqid(), 'user_id' => $customer->id,
            'customer_name' => 'Test Customer', 'customer_email' => $customer->email, 'customer_phone' => '01000000000',
            'governorate' => 'Cairo', 'city' => 'Nasr City', 'address' => 'Street 1',
            'subtotal' => 500, 'shipping_fee' => 0, 'total' => 500,
            'status' => 'processing', 'payment_method' => Order::PAYMENT_METHOD_COD,
        ]);

        $this->mock(PushNotificationService::class, function ($mock) use ($customer) {
            $mock->shouldReceive('sendToUser')->once()->withArgs(fn ($userId) => $userId === $customer->id);
        });

        $this->actingAs($admin)->patch(route('admin.orders.status', $order), ['status' => 'shipped'])->assertRedirect();
    }

    public function test_guest_order_status_change_never_attempts_a_push(): void
    {
        $admin = $this->makeAdmin();
        $order = Order::create([
            'order_number' => 'ORD-'.uniqid(), 'user_id' => null,
            'customer_name' => 'Guest Customer', 'customer_email' => '', 'customer_phone' => '01000000000',
            'governorate' => 'Cairo', 'city' => 'Nasr City', 'address' => 'Street 1',
            'subtotal' => 500, 'shipping_fee' => 0, 'total' => 500,
            'status' => 'processing', 'payment_method' => Order::PAYMENT_METHOD_COD,
        ]);

        $this->mock(PushNotificationService::class, function ($mock) {
            $mock->shouldNotReceive('sendToUser');
        });

        $this->actingAs($admin)->patch(route('admin.orders.status', $order), ['status' => 'shipped'])->assertRedirect();
    }

    // ---------------------------------------------------------------
    // PushNotificationService itself
    // ---------------------------------------------------------------

    public function test_service_is_not_configured_without_vapid_keys(): void
    {
        config(['services.webpush.public_key' => null, 'services.webpush.private_key' => null]);

        $this->assertFalse((new PushNotificationService)->isConfigured());
    }

    public function test_service_is_configured_when_vapid_keys_are_set(): void
    {
        config([
            'services.webpush.public_key' => 'BMwq81pCDrgeJN6jb9kJMByZYBDwMRdYFGtzd4MwQvDZE9tYF-UeSAv0P8eTMVxew8F0STj8sTodhKgSPv6l3kw',
            'services.webpush.private_key' => 'tYlfHNPaXnAe84x9t8Bl1YAc_7TVARRelvOqOSnwrTA',
            'services.webpush.subject' => 'mailto:test@example.com',
        ]);

        $this->assertTrue((new PushNotificationService)->isConfigured());
    }

    public function test_send_to_subscription_is_a_safe_no_op_when_not_configured(): void
    {
        config(['services.webpush.public_key' => null, 'services.webpush.private_key' => null]);

        $subscription = PushSubscription::create([
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/x', 'p256dh' => 'a', 'auth' => 'b',
        ]);

        // Must not throw even though the service has nothing configured.
        (new PushNotificationService)->sendToSubscription($subscription, 'Title', 'Body');
        $this->assertTrue(true);
    }
}
