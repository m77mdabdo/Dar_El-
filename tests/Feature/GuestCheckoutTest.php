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

    /**
     * Guest checkout now requires a CAPTCHA answer (see
     * CheckoutController::generateCaptcha()) — a real submission always
     * follows a GET to checkout.show(), which is what seeds the session
     * answer, so tests do the same rather than reaching into the session
     * directly. Every call gets a fresh challenge, since show() generates a
     * new one on every visit and withValidator() consumes it exactly once.
     */
    protected function postGuestCheckout(array $overrides = []): \Illuminate\Testing\TestResponse
    {
        $this->get(route('checkout.show'));

        return $this->post(route('checkout.store'), $this->guestCheckoutPayload(array_merge(
            ['captcha_answer' => session('checkout_captcha_answer')],
            $overrides
        )));
    }

    // ---------------------------------------------------------------
    // The essential-fields-only path
    // ---------------------------------------------------------------

    public function test_guest_can_complete_checkout_with_only_name_phone_and_address(): void
    {
        $product = $this->makeProduct();
        $this->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();

        $response = $this->postGuestCheckout();

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

        $response = $this->postGuestCheckout([
            'customer_email' => 'guest@example.com',
        ]);

        $response->assertSessionHasNoErrors();
        $order = Order::first();
        $this->assertSame('guest@example.com', $order->customer_email);
        $this->assertNull($order->user_id);
    }

    public function test_guest_checkout_rejects_an_invalid_email_if_one_is_given(): void
    {
        $product = $this->makeProduct();
        $this->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();

        $response = $this->postGuestCheckout([
            'customer_email' => 'not-an-email',
        ]);

        $response->assertSessionHasErrors(['customer_email']);
    }

    public function test_guest_checkout_still_requires_name_phone_and_address(): void
    {
        $product = $this->makeProduct();
        $this->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();

        $response = $this->postGuestCheckout([
            'customer_name' => '', 'customer_phone' => '', 'address' => '',
        ]);

        $response->assertSessionHasErrors(['customer_name', 'customer_phone', 'address']);
    }

    public function test_guest_order_decrements_stock_and_creates_order_items_normally(): void
    {
        $product = $this->makeProduct(stock: 5);
        $this->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 2])->assertOk();

        $this->postGuestCheckout()->assertSessionHasNoErrors();

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
            $this->postGuestCheckout()->assertSessionHasNoErrors();
        }

        $this->assertSame(5, Order::where('customer_phone', '01000000000')->count());

        $sixthProduct = $this->makeProduct();
        $this->postJson(route('cart.add', $sixthProduct), ['size' => 'M', 'quantity' => 1])->assertOk();
        $response = $this->postGuestCheckout();

        $response->assertSessionHasErrors(['customer_phone']);
        $this->assertSame(5, Order::where('customer_phone', '01000000000')->count());
    }

    public function test_the_phone_rate_limit_does_not_count_orders_older_than_the_window(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $order = Order::create([
                'order_number' => 'ORD-OLD-'.uniqid(),
                'customer_name' => 'Old Order', 'customer_email' => '', 'customer_phone' => '01000000000',
                'customer_phone_normalized' => \App\Support\PhoneNumberNormalizer::normalize('01000000000'),
                'governorate' => 'Cairo', 'city' => 'Nasr City', 'address' => 'Street',
                'address_rate_limit_key' => \App\Support\CheckoutAddressNormalizer::key('Cairo', 'Nasr City', 'Street'),
                'subtotal' => 1000, 'shipping_fee' => 0, 'total' => 1000, 'status' => 'pending',
                'payment_method' => Order::PAYMENT_METHOD_COD,
            ]);
            $order->forceFill(['created_at' => now()->subHours(2)])->save();
        }

        $product = $this->makeProduct();
        $this->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();
        $response = $this->postGuestCheckout();

        $response->assertSessionHasNoErrors();
    }

    public function test_different_phone_numbers_are_not_affected_by_each_others_rate_limit(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $product = $this->makeProduct();
            $this->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();
            $this->postGuestCheckout()->assertSessionHasNoErrors();
        }

        $product = $this->makeProduct();
        $this->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();
        // A different address too, not just a different phone — otherwise
        // this submission would trip the (correctly) independent address-
        // based limit instead of proving the phone limit alone is scoped
        // per-phone. See test_a_different_delivery_address_is_not_affected_by_the_addresss_rate_limit
        // for that limit's own isolation test.
        $response = $this->postGuestCheckout([
            'customer_phone' => '01099998888',
            'address' => '456 Another St',
        ]);

        $response->assertSessionHasNoErrors();
    }

    /**
     * Before PhoneNumberNormalizer existed, the phone-based rate limit
     * compared customer_phone as a raw string — so an attacker (or a
     * genuine repeat customer, either way the guard needs to catch it)
     * could place 5 real orders as "01012345678", then keep going forever
     * just by adding a space, a dash, or switching to the "+20"/"0020"
     * international form on every following submission, since none of
     * those strings ever matched an earlier raw value. Every variant below
     * is the exact same real phone number.
     */
    public function test_the_phone_rate_limit_cannot_be_bypassed_by_formatting_variations(): void
    {
        $phoneVariants = [
            '01012345678',
            '010 1234 5678',
            '010-1234-5678',
            '+20 10 1234 5678',
            '0020 10 1234 5678',
        ];

        foreach ($phoneVariants as $variant) {
            $product = $this->makeProduct();
            $this->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();
            $this->postGuestCheckout(['customer_phone' => $variant])->assertSessionHasNoErrors();
        }

        $this->assertSame(5, Order::count());

        $product = $this->makeProduct();
        $this->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();
        $response = $this->postGuestCheckout(['customer_phone' => '(010) 1234-5678']);

        $response->assertSessionHasErrors(['customer_phone']);
        $this->assertSame(5, Order::count());
    }

    // ---------------------------------------------------------------
    // Address-based rate limit (Finding #1's second, independent guard)
    // ---------------------------------------------------------------

    /**
     * Catches the case the phone limit alone can't: the same real target
     * (address) receiving many orders from a stream of different phone
     * numbers, which used to sail through untouched since nothing compared
     * addresses across orders at all.
     */
    public function test_the_address_rate_limit_blocks_repeated_orders_to_the_same_address_even_with_different_phones(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $product = $this->makeProduct();
            $this->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();
            $this->postGuestCheckout(['customer_phone' => '010000'.str_pad((string) $i, 5, '0', STR_PAD_LEFT)])
                ->assertSessionHasNoErrors();
        }

        $this->assertSame(5, Order::count());

        $product = $this->makeProduct();
        $this->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();
        $response = $this->postGuestCheckout(['customer_phone' => '01099999999']);

        $response->assertSessionHasErrors(['address']);
        $this->assertSame(5, Order::count());
    }

    /**
     * The 5 orders below are placed under 5 differently-formatted versions
     * of the same real address (case, whitespace, punctuation) — proving
     * CheckoutAddressNormalizer actually collapses them into the same
     * rate-limit bucket, not just that a byte-for-byte identical address
     * string (the case covered by the test above) does.
     */
    public function test_the_address_rate_limit_also_catches_incidental_formatting_differences(): void
    {
        $addressVariants = [
            ['governorate' => 'Cairo', 'city' => 'Nasr City', 'address' => '123 Test St.'],
            ['governorate' => 'cairo', 'city' => 'nasr city', 'address' => '123 test st'],
            ['governorate' => ' Cairo ', 'city' => ' Nasr  City ', 'address' => '123   Test   St'],
            ['governorate' => 'CAIRO', 'city' => 'NASR CITY', 'address' => '123, Test, St'],
            ['governorate' => 'Cairo', 'city' => 'Nasr City', 'address' => '123 Test St'],
        ];

        foreach ($addressVariants as $i => $addressFields) {
            $product = $this->makeProduct();
            $this->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();
            $this->postGuestCheckout(array_merge($addressFields, [
                'customer_phone' => '010000'.str_pad((string) $i, 5, '0', STR_PAD_LEFT),
            ]))->assertSessionHasNoErrors();
        }

        $this->assertSame(5, Order::count());

        $product = $this->makeProduct();
        $this->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();
        $response = $this->postGuestCheckout([
            'customer_phone' => '01099999999',
            'governorate' => '  CAIRO', 'city' => 'Nasr City,', 'address' => '123. Test. St',
        ]);

        $response->assertSessionHasErrors(['address']);
        $this->assertSame(5, Order::count());
    }

    public function test_a_different_delivery_address_is_not_affected_by_the_addresss_rate_limit(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $product = $this->makeProduct();
            $this->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();
            $this->postGuestCheckout(['customer_phone' => '010000'.str_pad((string) $i, 5, '0', STR_PAD_LEFT)])
                ->assertSessionHasNoErrors();
        }

        $product = $this->makeProduct();
        $this->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();
        $response = $this->postGuestCheckout([
            'customer_phone' => '01099999999',
            'address' => '999 A Totally Different St',
        ]);

        $response->assertSessionHasNoErrors();
    }

    // ---------------------------------------------------------------
    // Checkout idempotency (Finding #3): a double-click/retry resubmission
    // of the exact same cart must not create a second order.
    // ---------------------------------------------------------------

    public function test_a_resubmission_of_the_same_cart_and_phone_reuses_the_existing_order_instead_of_duplicating(): void
    {
        $product = $this->makeProduct(stock: 5);
        $this->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 2])->assertOk();

        $first = $this->postGuestCheckout();
        $first->assertSessionHasNoErrors();
        $firstOrder = Order::first();

        // The first submission already cleared the cart — re-add the exact
        // same contents to simulate the real trigger for this bug: a slow
        // response, and the customer clicks "Place Order" a second time
        // before the page navigates away, resubmitting the same form.
        $this->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 2])->assertOk();
        $second = $this->postGuestCheckout();

        $second->assertSessionHasNoErrors();
        $second->assertRedirect(route('checkout.success', $firstOrder));

        $this->assertSame(1, Order::count());
        // Stock decremented once (5 - 2), not twice (5 - 4) — the real
        // damage a duplicate order does beyond an extra database row.
        $this->assertSame(3, $product->sizes()->first()->fresh()->stock);
    }

    public function test_a_genuinely_new_order_from_the_same_phone_shortly_after_is_not_treated_as_a_duplicate(): void
    {
        $productA = $this->makeProduct();
        $this->postJson(route('cart.add', $productA), ['size' => 'M', 'quantity' => 1])->assertOk();
        $this->postGuestCheckout()->assertSessionHasNoErrors();

        $productB = $this->makeProduct();
        $this->postJson(route('cart.add', $productB), ['size' => 'M', 'quantity' => 1])->assertOk();
        $response = $this->postGuestCheckout();

        $response->assertSessionHasNoErrors();
        $this->assertSame(2, Order::count());
    }

    // ---------------------------------------------------------------
    // CAPTCHA (guest-only)
    // ---------------------------------------------------------------

    public function test_guest_checkout_is_rejected_without_a_captcha_answer(): void
    {
        $product = $this->makeProduct();
        $this->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();
        $this->get(route('checkout.show'));

        $response = $this->post(route('checkout.store'), $this->guestCheckoutPayload());

        $response->assertSessionHasErrors(['captcha_answer']);
        $this->assertSame(0, Order::count());
    }

    public function test_guest_checkout_is_rejected_with_a_wrong_captcha_answer(): void
    {
        $product = $this->makeProduct();
        $this->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();
        $this->get(route('checkout.show'));
        $correctAnswer = session('checkout_captcha_answer');

        $response = $this->post(route('checkout.store'), $this->guestCheckoutPayload([
            'captcha_answer' => $correctAnswer + 1,
        ]));

        $response->assertSessionHasErrors(['captcha_answer']);
        $this->assertSame(0, Order::count());
    }

    /**
     * The answer is pulled from the session (consumed) on every validation
     * attempt regardless of outcome — proves a failed attempt can't just be
     * retried with the same correct answer once the customer fixes whatever
     * else was wrong, closing off a brute-force loop against one challenge.
     */
    public function test_a_captcha_answer_cannot_be_reused_after_a_failed_attempt(): void
    {
        $product = $this->makeProduct();
        $this->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();
        $this->get(route('checkout.show'));
        $answer = session('checkout_captcha_answer');

        $this->post(route('checkout.store'), $this->guestCheckoutPayload([
            'customer_name' => '', 'captcha_answer' => $answer,
        ]))->assertSessionHasErrors(['customer_name']);

        $replay = $this->post(route('checkout.store'), $this->guestCheckoutPayload([
            'captcha_answer' => $answer,
        ]));

        $replay->assertSessionHasErrors(['captcha_answer']);
        $this->assertSame(0, Order::count());
    }

    public function test_authenticated_checkout_does_not_require_a_captcha_answer(): void
    {
        $user = \App\Models\User::factory()->create();
        $product = $this->makeProduct();
        $this->actingAs($user)->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();

        $response = $this->actingAs($user)->post(route('checkout.store'), $this->guestCheckoutPayload([
            'customer_name' => $user->name, 'customer_email' => $user->email,
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
        $this->postGuestCheckout()->assertSessionHasNoErrors();

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
        $this->postGuestCheckout([
            'customer_email' => 'guest@example.com',
        ])->assertSessionHasNoErrors();

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
