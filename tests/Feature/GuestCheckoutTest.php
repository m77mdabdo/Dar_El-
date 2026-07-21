<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function makeProduct(int $stock = 5): Product
    {
        $category = Category::create([
            'name_ar' => 'عبايات', 'name_en' => 'Abayas', 'slug' => 'abayas-'.uniqid(), 'is_active' => true, 'sort_order' => 1,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name_ar' => 'عباية', 'name_en' => 'Abaya', 'slug' => 'abaya-'.uniqid(),
            'price' => 1000, 'is_active' => true, 'is_featured' => false,
        ]);

        $product->sizes()->create(['size' => 'M', 'stock' => $stock]);

        return $product;
    }

    protected function guestCheckoutPayload(array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'Guest Shopper',
            'customer_phone' => '01000000000',
            'governorate' => 'Cairo',
            'city' => 'Nasr City',
            'address' => '123 Test St',
            'shipping_method_id' => 'standard',
            'payment_method' => Order::PAYMENT_METHOD_COD,
        ], $overrides);
    }

    // ---------------------------------------------------------------
    // The essential-fields-only path
    // ---------------------------------------------------------------

    public function test_guest_can_complete_checkout_with_only_name_phone_and_address(): void
    {
        $product = $this->makeProduct();
        $this->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();

        $response = $this->post(route('checkout.store'), $this->guestCheckoutPayload());

        $response->assertSessionHasNoErrors();
        $order = Order::first();
        $this->assertNotNull($order);
        $response->assertRedirect(route('checkout.success', $order));

        $this->assertNull($order->user_id);
        $this->assertSame('Guest Shopper', $order->customer_name);
        $this->assertSame('01000000000', $order->customer_phone);
        $this->assertSame('', $order->customer_email);
    }

    public function test_guest_checkout_still_accepts_an_optional_email(): void
    {
        $product = $this->makeProduct();
        $this->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();

        $response = $this->post(route('checkout.store'), $this->guestCheckoutPayload([
            'customer_email' => 'guest@example.com',
        ]));

        $response->assertSessionHasNoErrors();
        $order = Order::first();
        $this->assertSame('guest@example.com', $order->customer_email);
        $this->assertNull($order->user_id);
    }

    public function test_guest_checkout_rejects_an_invalid_email_if_one_is_given(): void
    {
        $product = $this->makeProduct();
        $this->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();

        $response = $this->post(route('checkout.store'), $this->guestCheckoutPayload([
            'customer_email' => 'not-an-email',
        ]));

        $response->assertSessionHasErrors(['customer_email']);
    }

    public function test_guest_checkout_still_requires_name_phone_and_address(): void
    {
        $product = $this->makeProduct();
        $this->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();

        $response = $this->post(route('checkout.store'), $this->guestCheckoutPayload([
            'customer_name' => '', 'customer_phone' => '', 'address' => '',
        ]));

        $response->assertSessionHasErrors(['customer_name', 'customer_phone', 'address']);
    }

    public function test_guest_order_decrements_stock_and_creates_order_items_normally(): void
    {
        $product = $this->makeProduct(stock: 5);
        $this->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 2])->assertOk();

        $this->post(route('checkout.store'), $this->guestCheckoutPayload())->assertSessionHasNoErrors();

        $this->assertSame(3, $product->sizes()->first()->fresh()->stock);
        $this->assertSame(1, Order::first()->items()->count());
    }

    // ---------------------------------------------------------------
    // Phone-based rate limit (the OTP gate's replacement abuse guard)
    // ---------------------------------------------------------------

    public function test_the_same_phone_number_is_blocked_after_5_orders_within_an_hour(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $product = $this->makeProduct();
            $this->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();
            $this->post(route('checkout.store'), $this->guestCheckoutPayload())->assertSessionHasNoErrors();
        }

        $this->assertSame(5, Order::where('customer_phone', '01000000000')->count());

        $sixthProduct = $this->makeProduct();
        $this->postJson(route('cart.add', $sixthProduct), ['size' => 'M', 'quantity' => 1])->assertOk();
        $response = $this->post(route('checkout.store'), $this->guestCheckoutPayload());

        $response->assertSessionHasErrors(['customer_phone']);
        $this->assertSame(5, Order::where('customer_phone', '01000000000')->count());
    }

    public function test_the_phone_rate_limit_does_not_count_orders_older_than_the_window(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $order = Order::create([
                'order_number' => 'ORD-OLD-'.uniqid(),
                'customer_name' => 'Old Order', 'customer_email' => '', 'customer_phone' => '01000000000',
                'governorate' => 'Cairo', 'city' => 'Nasr City', 'address' => 'Street',
                'subtotal' => 1000, 'shipping_fee' => 0, 'total' => 1000, 'status' => 'pending',
                'payment_method' => Order::PAYMENT_METHOD_COD,
            ]);
            $order->forceFill(['created_at' => now()->subHours(2)])->save();
        }

        $product = $this->makeProduct();
        $this->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();
        $response = $this->post(route('checkout.store'), $this->guestCheckoutPayload());

        $response->assertSessionHasNoErrors();
    }

    public function test_different_phone_numbers_are_not_affected_by_each_others_rate_limit(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $product = $this->makeProduct();
            $this->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();
            $this->post(route('checkout.store'), $this->guestCheckoutPayload())->assertSessionHasNoErrors();
        }

        $product = $this->makeProduct();
        $this->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();
        $response = $this->post(route('checkout.store'), $this->guestCheckoutPayload([
            'customer_phone' => '01099998888',
        ]));

        $response->assertSessionHasNoErrors();
    }

    // ---------------------------------------------------------------
    // Guest order tracking still works (existing feature, null user_id)
    // ---------------------------------------------------------------

    public function test_a_guest_order_with_no_email_can_still_be_tracked_by_order_number_and_phone(): void
    {
        $product = $this->makeProduct();
        $this->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();
        $this->post(route('checkout.store'), $this->guestCheckoutPayload())->assertSessionHasNoErrors();

        $order = Order::first();
        $this->assertSame('', $order->customer_email);

        $response = $this->post(route('track-order.lookup'), [
            'order_number' => $order->order_number,
            'contact' => '01000000000',
        ]);

        $response->assertRedirect();
        $trackerResponse = $this->get($response->headers->get('Location'));
        $trackerResponse->assertOk();
        $trackerResponse->assertSee($order->order_number);
    }

    // ---------------------------------------------------------------
    // Success page: guest account-creation prompt
    // ---------------------------------------------------------------

    public function test_success_page_shows_the_create_account_prompt_for_a_guest_order(): void
    {
        $product = $this->makeProduct();
        $this->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();
        $this->post(route('checkout.store'), $this->guestCheckoutPayload([
            'customer_email' => 'guest@example.com',
        ]))->assertSessionHasNoErrors();

        $order = Order::first();

        $response = $this->get(route('checkout.success', $order));

        $response->assertOk();
        $response->assertSee(__('Create an account to track all your future orders easily.'));
        // The register link is pre-filled from this exact order, so the
        // customer doesn't have to retype what they just gave at checkout.
        // Blade HTML-escapes the & between query params (&amp;), and the
        // route() helper encodes the space in "Guest Shopper" as %20 — so
        // rather than reconstruct that exact encoded string, just parse
        // the actual href out of the response and inspect its real query.
        preg_match('/href="([^"]*\/register\?[^"]*)"/', $response->getContent(), $matches);
        $this->assertNotEmpty($matches, 'Register link not found on the success page.');

        parse_str(parse_url(html_entity_decode($matches[1]), PHP_URL_QUERY), $query);
        $this->assertSame('Guest Shopper', $query['name']);
        $this->assertSame('guest@example.com', $query['email']);
        $this->assertSame('01000000000', $query['phone']);
    }

    public function test_success_page_does_not_show_the_account_prompt_when_already_authenticated(): void
    {
        $user = \App\Models\User::factory()->create();
        $product = $this->makeProduct();
        $this->actingAs($user)->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();
        $this->actingAs($user)->post(route('checkout.store'), $this->guestCheckoutPayload([
            'customer_name' => $user->name, 'customer_email' => $user->email,
        ]))->assertSessionHasNoErrors();

        $order = Order::first();

        $response = $this->actingAs($user)->get(route('checkout.success', $order));

        $response->assertOk();
        $response->assertDontSee(__('Create an account to track all your future orders easily.'));
    }
}
