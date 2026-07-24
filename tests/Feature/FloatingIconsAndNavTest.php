<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The order-tracking float (partials/order-tracking-float.blade.php) was
 * replaced by a cart float in the exact same stack slot, and order tracking
 * moved into the navbar next to Wishlist instead — see
 * layouts/storefront.blade.php. These tests exercise both halves of that
 * swap; the actual zero-overlap-at-any-scroll-position claim is a rendering
 * concern this HTTP-level test can't see, verified separately with
 * Puppeteer.
 */
class FloatingIconsAndNavTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_floating_cart_button_renders_in_the_old_tracking_floats_slot(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('id="dj-cart-float"', false);
        $response->assertSee('onclick="djOpenCart()"', false);
    }

    public function test_the_old_floating_tracking_button_no_longer_renders(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertDontSee('id="dj-tracking-float"', false);
    }

    /**
     * The footer's own "Quick Links" also links to track-order.form
     * unconditionally, so a bare assertSee() on that href alone would pass
     * even if the new nav link were missing entirely — asserting on the nav
     * link's own class="dj-cart-btn" (the footer link has no class at all)
     * is what actually proves this specific link exists.
     */
    public function test_guests_see_an_order_tracking_nav_link_to_the_lookup_form(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('href="'.route('track-order.form').'" class="dj-cart-btn"', false);
    }

    public function test_authenticated_customers_see_an_order_tracking_nav_link_to_their_order_history(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('customer', 'web'));

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk();
        $response->assertSee('href="'.route('account.orders.index').'" class="dj-cart-btn"', false);
    }

    public function test_the_floating_cart_button_renders_regardless_of_auth_state(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('customer', 'web'));

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk();
        $response->assertSee('id="dj-cart-float"', false);
    }
}
