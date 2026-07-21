<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Checkout no longer gates on auth/email-verification at all — guests can
 * reach and complete it with just name/phone/address (see
 * StoreCheckoutRequest and GuestCheckoutTest). Registration itself still
 * requires OTP verification exactly as before; it's just no longer forced
 * before checkout. See RegisteredUserController/OtpVerificationController's
 * own test coverage for that unchanged mechanism.
 */
class CheckoutAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_reach_checkout_without_being_redirected_to_login(): void
    {
        $response = $this->get('/checkout');

        // Cart is empty in this test, so the controller itself redirects to
        // the cart page — proving nothing upstream (no auth/verified guard)
        // intercepted the request first. Not route('login').
        $response->assertRedirect(route('cart.index'));
    }

    public function test_unverified_authenticated_user_can_also_reach_checkout(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/checkout');

        // Same as the guest case above — an unverified account has no less
        // identity than a guest now, so it isn't held to a stricter
        // standard than a complete stranger would be.
        $response->assertRedirect(route('cart.index'));
    }

    public function test_verified_user_can_reach_checkout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/checkout');

        $response->assertRedirect(route('cart.index'));
    }
}
