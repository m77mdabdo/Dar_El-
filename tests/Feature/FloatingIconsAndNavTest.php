<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
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
 *
 * The navbar's own cart button was later removed entirely (the floating
 * button is now the only cart entry point) — the tests further down cover
 * that removal and the floating button's new live count badge. The actual
 * "updates live via AJAX without a page reload" behavior is a JS/DOM
 * concern this HTTP-level test can't see either, verified separately with
 * Puppeteer against a real djAddToCart() call.
 */
class FloatingIconsAndNavTest extends TestCase
{
    use RefreshDatabase;

    protected function makeProduct(int $stock = 5): Product
    {
        $category = Category::create([
            'name_ar' => 'فئة', 'name_en' => 'Category', 'slug' => 'cat-'.uniqid(), 'is_active' => true, 'sort_order' => 1,
        ]);

        $product = Product::create([
            'category_id' => $category->id, 'name_ar' => 'منتج', 'name_en' => 'Product',
            'slug' => 'product-'.uniqid(), 'price' => 500, 'is_active' => true, 'is_featured' => false,
        ]);

        $product->sizes()->create(['size' => 'M', 'stock' => $stock]);

        return $product;
    }

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

    // ---------------------------------------------------------------
    // Navbar cart button removal — the floating icon is now the only entry point
    // ---------------------------------------------------------------

    public function test_the_navbar_no_longer_has_its_own_cart_button(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertDontSee('id="dj-cart-count"', false);
        // djOpenCart() is now wired to exactly one element (the float) —
        // if the navbar button ever came back, both would exist and this
        // assertion is what would actually catch that, not just "the float
        // still exists" (which stays true either way).
        $this->assertSame(1, substr_count($response->getContent(), 'onclick="djOpenCart()"'));
    }

    // ---------------------------------------------------------------
    // Floating cart button's live count badge
    // ---------------------------------------------------------------

    public function test_the_cart_badge_is_hidden_when_the_cart_is_empty(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('id="dj-cart-float-count" hidden', false);
    }

    public function test_the_cart_badge_shows_the_real_item_count_and_is_not_hidden(): void
    {
        $product = $this->makeProduct();
        $this->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 3])->assertOk();

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('id="dj-cart-float-count">3</span>', false);
        $response->assertDontSee('id="dj-cart-float-count" hidden', false);
    }

    public function test_the_cart_badge_reflects_quantity_across_multiple_items(): void
    {
        $productA = $this->makeProduct();
        $productB = $this->makeProduct();
        $this->postJson(route('cart.add', $productA), ['size' => 'M', 'quantity' => 2])->assertOk();
        $this->postJson(route('cart.add', $productB), ['size' => 'M', 'quantity' => 1])->assertOk();

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('id="dj-cart-float-count">3</span>', false);
    }
}
