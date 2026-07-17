<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OfferCountdownTest extends TestCase
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

    protected function makeProduct(string $name = 'Product', ?string $offerEndsAt = null): Product
    {
        $product = Product::create([
            'category_id' => $this->defaultCategory()->id,
            'name_ar' => $name, 'name_en' => $name, 'slug' => Str::slug($name).'-'.uniqid(),
            'price' => 1000, 'is_active' => true, 'is_featured' => false,
            'offer_ends_at' => $offerEndsAt,
        ]);
        $product->sizes()->create(['size' => 'M', 'stock' => 5]);

        return $product;
    }

    protected function admin(): User
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    // ---------------------------------------------------------------
    // Site-wide countdown
    // ---------------------------------------------------------------

    public function test_sitewide_countdown_renders_on_homepage_when_set_and_future(): void
    {
        Setting::set('sitewide_offer_end_at', now()->addDays(2)->format('Y-m-d\TH:i:s'));
        Setting::set('sitewide_offer_label', 'Weekend Offer');

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('id="dj-offer-countdown"', false);
        $response->assertSee('Weekend Offer');
    }

    public function test_sitewide_countdown_does_not_render_when_unset(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertDontSee('id="dj-offer-countdown"', false);
    }

    public function test_sitewide_countdown_does_not_render_when_in_the_past(): void
    {
        Setting::set('sitewide_offer_end_at', now()->subDay()->format('Y-m-d\TH:i:s'));
        Setting::set('sitewide_offer_label', 'Expired Offer');

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertDontSee('id="dj-offer-countdown"', false);
    }

    public function test_sitewide_countdown_renders_on_shop_listing_page(): void
    {
        Setting::set('sitewide_offer_end_at', now()->addDays(3)->format('Y-m-d\TH:i:s'));
        Setting::set('sitewide_offer_label', 'Shop Sale');

        $response = $this->get(route('shop.index'));

        $response->assertOk();
        $response->assertSee('id="dj-offer-countdown"', false);
        $response->assertSee('Shop Sale');
    }

    public function test_sitewide_countdown_renders_on_product_page_without_its_own_offer(): void
    {
        Setting::set('sitewide_offer_end_at', now()->addDays(1)->format('Y-m-d\TH:i:s'));
        Setting::set('sitewide_offer_label', 'Sitewide Sale');
        $product = $this->makeProduct('No Offer Product');

        $response = $this->get(route('shop.show', $product));

        $response->assertOk();
        $response->assertSee('id="dj-offer-countdown"', false);
        $response->assertSee('Sitewide Sale');
    }

    public function test_countdown_carries_the_correct_iso8601_end_timestamp(): void
    {
        $endsAt = now()->addDays(5)->startOfSecond();
        Setting::set('sitewide_offer_end_at', $endsAt->format('Y-m-d\TH:i:s'));
        Setting::set('sitewide_offer_label', 'Timed Sale');

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('data-ends-at="'.$endsAt->toIso8601String().'"', false);
    }

    // ---------------------------------------------------------------
    // Per-product countdown
    // ---------------------------------------------------------------

    public function test_per_product_countdown_renders_on_its_own_page(): void
    {
        $product = $this->makeProduct('Offer Product', now()->addDays(2)->format('Y-m-d\TH:i:s'));

        $response = $this->get(route('shop.show', $product));

        $response->assertOk();
        $response->assertSee('id="dj-offer-countdown"', false);
        $response->assertSee(__('Limited-Time Offer on This Item'));
    }

    public function test_per_product_countdown_takes_precedence_over_sitewide_on_its_own_page(): void
    {
        Setting::set('sitewide_offer_end_at', now()->addDays(10)->format('Y-m-d\TH:i:s'));
        Setting::set('sitewide_offer_label', 'Sitewide Sale');

        $productEndsAt = now()->addHours(3)->startOfSecond();
        $product = $this->makeProduct('Priority Offer Product', $productEndsAt->format('Y-m-d\TH:i:s'));

        $response = $this->get(route('shop.show', $product));

        $response->assertOk();
        // Only one countdown banner ever renders on the page.
        $response->assertSeeInOrder(['id="dj-offer-countdown"']);
        $response->assertSee(__('Limited-Time Offer on This Item'));
        $response->assertDontSee('Sitewide Sale');
        $response->assertSee('data-ends-at="'.$productEndsAt->toIso8601String().'"', false);
    }

    public function test_expired_per_product_offer_falls_back_to_sitewide_countdown(): void
    {
        Setting::set('sitewide_offer_end_at', now()->addDays(4)->format('Y-m-d\TH:i:s'));
        Setting::set('sitewide_offer_label', 'Fallback Sale');
        $product = $this->makeProduct('Expired Offer Product', now()->subDay()->format('Y-m-d\TH:i:s'));

        $response = $this->get(route('shop.show', $product));

        $response->assertOk();
        $response->assertSee('Fallback Sale');
        $response->assertDontSee(__('Limited-Time Offer on This Item'));
    }

    public function test_product_card_shows_offer_badge_when_active(): void
    {
        // 5h30m out, not on an hour boundary, so a few milliseconds of test
        // execution time can never flip diffInHours()'s truncation and make
        // this assertion flaky.
        $this->makeProduct('Badge Product', now()->addHours(5)->addMinutes(30)->format('Y-m-d\TH:i:s'));

        $response = $this->get(route('shop.index'));

        $response->assertOk();
        $response->assertSee('dj-offer-badge', false);
        $response->assertSee(__('Ends in :count h', ['count' => 5]));
    }

    public function test_product_card_does_not_show_offer_badge_without_an_active_offer(): void
    {
        $this->makeProduct('No Badge Product');

        $response = $this->get(route('shop.index'));

        $response->assertOk();
        // The shared .dj-offer-badge CSS rule (emitted once via @once
        // regardless of any card's own state) will legitimately appear in
        // the page - only the actual badge element must be absent.
        $response->assertDontSee('<span class="dj-offer-badge">', false);
    }

    // ---------------------------------------------------------------
    // Admin
    // ---------------------------------------------------------------

    public function test_admin_can_set_and_clear_sitewide_offer_settings(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->patch(route('admin.settings.update'), [
            'sitewide_offer_end_at' => '2027-01-01T10:00',
            'sitewide_offer_label' => 'عرض نهاية الأسبوع',
        ])->assertSessionHasNoErrors();

        $this->assertSame('2027-01-01T10:00', Setting::get('sitewide_offer_end_at'));
        $this->assertSame('عرض نهاية الأسبوع', Setting::get('sitewide_offer_label'));

        $this->actingAs($admin)->patch(route('admin.settings.update'), [
            'sitewide_offer_end_at' => '',
            'sitewide_offer_label' => '',
        ])->assertSessionHasNoErrors();

        $this->assertNull(Setting::get('sitewide_offer_end_at'));
        $this->assertNull(Setting::get('sitewide_offer_label'));
    }

    public function test_admin_can_set_and_clear_a_products_offer_ends_at(): void
    {
        $admin = $this->admin();
        $product = $this->makeProduct('Editable Offer Product');

        $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'category_id' => $product->category_id,
            'name_ar' => $product->name_ar, 'name_en' => $product->name_en,
            'price' => $product->price, 'status' => 'published',
            'offer_ends_at' => '2027-06-01T12:00',
        ])->assertSessionHasNoErrors();

        $this->assertSame('2027-06-01 12:00:00', $product->fresh()->offer_ends_at->format('Y-m-d H:i:s'));

        // update() regenerates the slug from name_en on every save (an
        // existing, unrelated behavior) — makeProduct()'s slug carries a
        // uniqid() suffix that name_en alone doesn't, so it changes after
        // the first PUT above. Re-fetch before building the second PUT's
        // URL, or it route-model-binds against the now-stale old slug.
        $product = $product->fresh();

        $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'category_id' => $product->category_id,
            'name_ar' => $product->name_ar, 'name_en' => $product->name_en,
            'price' => $product->price, 'status' => 'published',
            'offer_ends_at' => '',
        ])->assertSessionHasNoErrors();

        $this->assertNull($product->fresh()->offer_ends_at);
    }
}
