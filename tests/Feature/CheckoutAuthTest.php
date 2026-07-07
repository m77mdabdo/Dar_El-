<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_with_message(): void
    {
        $response = $this->get('/checkout');

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status', __('Please login or create an account to complete your order.'));
        $this->assertSame(route('checkout.show'), session('url.intended'));
    }

    public function test_unverified_user_is_redirected_to_otp_verification(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/checkout');

        $response->assertRedirect(route('otp.notice'));
    }

    public function test_verified_user_passes_the_checkout_guard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/checkout');

        // Cart is empty in this test, so the controller itself redirects to
        // the cart page — proving the auth/verified guard let the request through.
        $response->assertRedirect(route('cart.index'));
    }
}
