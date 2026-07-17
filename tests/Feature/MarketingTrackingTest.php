<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected ?Category $defaultCategory = null;

    protected function defaultCategory(): Category
    {
        return $this->defaultCategory ??= Category::create([
            'name_ar' => 'General', 'name_en' => 'General', 'slug' => 'general-'.uniqid(),
            'is_active' => true, 'sort_order' => 1,
        ]);
    }

    protected function makeProduct(string $name = 'Product', int $price = 1000): Product
    {
        $product = Product::create([
            'category_id' => $this->defaultCategory()->id,
            'name_ar' => $name, 'name_en' => $name, 'slug' => 'product-'.uniqid(),
            'price' => $price, 'is_active' => true, 'is_featured' => false,
        ]);
        $product->sizes()->create(['size' => 'M', 'stock' => 5]);

        return $product;
    }

    protected function clearAllTrackingIds(): void
    {
        Setting::set('meta_pixel_id', '');
        Setting::set('tiktok_pixel_id', '');
        Setting::set('ga4_measurement_id', '');
    }

    // ---------------------------------------------------------------
    // Base snippets — each platform independent, absent when empty
    // ---------------------------------------------------------------

    public function test_meta_pixel_snippet_renders_only_when_id_is_set(): void
    {
        $this->clearAllTrackingIds();
        $this->get(route('home'))->assertOk()->assertDontSee('connect.facebook.net', false);

        Setting::set('meta_pixel_id', '1234567890123456');
        $response = $this->get(route('home'));
        $response->assertOk();
        $response->assertSee('connect.facebook.net', false);
        $response->assertSee('1234567890123456', false);
        // The other two platforms stay absent — each is independent.
        $response->assertDontSee('analytics.tiktok.com', false);
        $response->assertDontSee('googletagmanager.com', false);
    }

    public function test_tiktok_pixel_snippet_renders_only_when_id_is_set(): void
    {
        $this->clearAllTrackingIds();
        $this->get(route('home'))->assertOk()->assertDontSee('analytics.tiktok.com', false);

        Setting::set('tiktok_pixel_id', 'C4A1B2C3D4E5F6G7H8I9');
        $response = $this->get(route('home'));
        $response->assertOk();
        $response->assertSee('analytics.tiktok.com', false);
        $response->assertSee('C4A1B2C3D4E5F6G7H8I9', false);
        $response->assertDontSee('connect.facebook.net', false);
        $response->assertDontSee('googletagmanager.com', false);
    }

    public function test_ga4_snippet_renders_only_when_id_is_set(): void
    {
        $this->clearAllTrackingIds();
        $this->get(route('home'))->assertOk()->assertDontSee('googletagmanager.com', false);

        Setting::set('ga4_measurement_id', 'G-ABC1234567');
        $response = $this->get(route('home'));
        $response->assertOk();
        $response->assertSee('googletagmanager.com', false);
        $response->assertSee('G-ABC1234567', false);
        $response->assertDontSee('connect.facebook.net', false);
        $response->assertDontSee('analytics.tiktok.com', false);
    }

    public function test_all_three_platforms_can_be_configured_independently_at_once(): void
    {
        Setting::set('meta_pixel_id', '111111111111111');
        Setting::set('tiktok_pixel_id', 'TTPIXEL123');
        Setting::set('ga4_measurement_id', 'G-ZZZ9999999');

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('connect.facebook.net', false);
        $response->assertSee('analytics.tiktok.com', false);
        $response->assertSee('googletagmanager.com', false);
    }

    public function test_no_tracking_scripts_render_at_all_when_every_id_is_empty(): void
    {
        $this->clearAllTrackingIds();

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertDontSee('connect.facebook.net', false);
        $response->assertDontSee('analytics.tiktok.com', false);
        $response->assertDontSee('googletagmanager.com', false);
        // The shared dispatcher itself still loads regardless (needed by
        // pages calling window.djTrack even with 0 platforms configured).
        $response->assertSee('window.djTrack', false);
    }

    public function test_admin_layout_never_includes_tracking_scripts(): void
    {
        Setting::set('meta_pixel_id', '111111111111111');

        $admin = \App\Models\User::factory()->create();
        $admin->assignRole(\Spatie\Permission\Models\Role::findOrCreate('admin', 'web'));

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertDontSee('connect.facebook.net', false);
    }

    // ---------------------------------------------------------------
    // View product event
    // ---------------------------------------------------------------

    public function test_product_page_fires_view_item_with_product_id_name_price(): void
    {
        Setting::set('ga4_measurement_id', 'G-TESTID1234');
        $product = $this->makeProduct('Tracked Product', 750);

        $response = $this->get(route('shop.show', $product));

        $response->assertOk();
        $response->assertSee("djTrack('view_item'", false);
        $response->assertSee((string) $product->id, false);
        $response->assertSee('750', false);
    }

    // ---------------------------------------------------------------
    // Begin checkout event
    // ---------------------------------------------------------------

    public function test_checkout_page_fires_begin_checkout_with_cart_items_and_value(): void
    {
        Setting::set('ga4_measurement_id', 'G-TESTID1234');
        $product = $this->makeProduct('Checkout Product', 600);

        $user = \App\Models\User::factory()->create();
        $this->actingAs($user)->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 2])->assertOk();

        $response = $this->actingAs($user)->get(route('checkout.show'));

        $response->assertOk();
        $response->assertSee("djTrack('begin_checkout'", false);
        $response->assertSee((string) $product->id, false);
    }

    // ---------------------------------------------------------------
    // Purchase event — fires exactly once per order
    // ---------------------------------------------------------------

    protected function makeOrder(): Order
    {
        $product = $this->makeProduct('Purchased Product', 900);

        $order = Order::create([
            'order_number' => 'ORD-TRACK-'.uniqid(),
            'customer_name' => 'Test Customer', 'customer_email' => 'test@example.com', 'customer_phone' => '01000000000',
            'governorate' => 'Cairo', 'city' => 'Nasr City', 'address' => '123 Test St',
            'subtotal' => 900, 'shipping_fee' => 50, 'total' => 950,
            'status' => 'pending', 'payment_method' => 'cash_on_delivery',
        ]);

        OrderItem::create([
            'order_id' => $order->id, 'product_id' => $product->id, 'product_name' => $product->name_en,
            'size' => 'M', 'price' => 900, 'quantity' => 1,
        ]);

        return $order;
    }

    public function test_success_page_fires_purchase_event_with_order_id_value_currency(): void
    {
        Setting::set('ga4_measurement_id', 'G-TESTID1234');
        $order = $this->makeOrder();
        $viewer = \App\Models\User::factory()->create();

        $response = $this->actingAs($viewer)->get(route('checkout.success', $order));

        $response->assertOk();
        $response->assertSee("djTrack('purchase'", false);
        $response->assertSee($order->order_number, false);
        $response->assertSee('950', false);
        $this->assertNotNull($order->fresh()->purchase_event_fired_at);
    }

    public function test_purchase_event_does_not_fire_again_on_a_page_refresh(): void
    {
        Setting::set('ga4_measurement_id', 'G-TESTID1234');
        $order = $this->makeOrder();
        $viewer = \App\Models\User::factory()->create();

        $this->actingAs($viewer)->get(route('checkout.success', $order))->assertOk();
        $firedAt = $order->fresh()->purchase_event_fired_at;
        $this->assertNotNull($firedAt);

        // Refresh the same success page.
        $second = $this->actingAs($viewer)->get(route('checkout.success', $order));

        $second->assertOk();
        $second->assertDontSee("djTrack('purchase'", false);
        // The timestamp itself does not move on a re-view.
        $this->assertTrue($firedAt->equalTo($order->fresh()->purchase_event_fired_at));
    }

    public function test_purchase_event_still_omits_customer_pii(): void
    {
        Setting::set('ga4_measurement_id', 'G-TESTID1234');
        $order = $this->makeOrder();
        $viewer = \App\Models\User::factory()->create();

        $response = $this->actingAs($viewer)->get(route('checkout.success', $order));

        $response->assertOk();
        // The order confirmation page legitimately displays the address
        // elsewhere on the page (outside the tracking <script> block) —
        // this only checks the tracking payload itself never carries PII.
        $trackingScript = \Illuminate\Support\Str::between($response->getContent(), "djTrack('purchase'", '});');
        $this->assertStringNotContainsString('test@example.com', $trackingScript);
        $this->assertStringNotContainsString('01000000000', $trackingScript);
        $this->assertStringNotContainsString('Test Customer', $trackingScript);
        $this->assertStringNotContainsString('123 Test St', $trackingScript);
    }
}
