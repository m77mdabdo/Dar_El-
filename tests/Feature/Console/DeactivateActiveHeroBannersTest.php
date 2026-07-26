<?php

namespace Tests\Feature\Console;

use App\Models\Banner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DeactivateActiveHeroBannersTest extends TestCase
{
    use RefreshDatabase;

    protected function makeBanner(array $overrides = []): Banner
    {
        return Banner::create(array_merge([
            'type' => Banner::TYPE_HERO,
            'title_ar' => 'بانر', 'title_en' => 'Banner',
            'image' => 'banners/x.webp', 'is_active' => true, 'sort_order' => 0,
        ], $overrides));
    }

    public function test_reports_when_no_active_hero_banners_exist(): void
    {
        $this->artisan('banners:deactivate-hero')
            ->expectsOutputToContain('No active hero banners found')
            ->assertSuccessful();
    }

    public function test_deactivates_an_active_hero_banner(): void
    {
        $hero = $this->makeBanner(['title_en' => 'Live Hero', 'is_active' => true]);

        $this->artisan('banners:deactivate-hero')
            ->expectsOutputToContain('1 active hero banner(s) deactivated')
            ->assertSuccessful();

        $this->assertFalse($hero->fresh()->is_active);
    }

    public function test_leaves_already_inactive_hero_banners_and_non_hero_banners_untouched(): void
    {
        $inactiveHero = $this->makeBanner(['title_en' => 'Already Inactive Hero', 'is_active' => false]);
        $offer = Banner::create([
            'type' => Banner::TYPE_OFFER, 'title_ar' => 'عرض', 'title_en' => 'An Offer Banner',
            'image' => 'banners/offer.webp', 'is_active' => true, 'sort_order' => 0,
        ]);
        $collection = Banner::create([
            'type' => Banner::TYPE_COLLECTION, 'title_ar' => 'تشكيلة', 'title_en' => 'A Collection Banner',
            'image' => 'banners/collection.webp', 'is_active' => true, 'sort_order' => 0,
        ]);

        $this->artisan('banners:deactivate-hero')
            ->expectsOutputToContain('No active hero banners found')
            ->assertSuccessful();

        $this->assertFalse($inactiveHero->fresh()->is_active);
        $this->assertTrue($offer->fresh()->is_active);
        $this->assertTrue($collection->fresh()->is_active);
    }

    public function test_deactivates_multiple_active_hero_banners_and_lists_each_in_the_output(): void
    {
        $first = $this->makeBanner(['title_en' => 'First Hero', 'is_active' => true]);
        $second = $this->makeBanner(['title_en' => 'Second Hero', 'is_active' => true]);

        $this->artisan('banners:deactivate-hero')
            ->expectsOutputToContain('2 active hero banner(s) deactivated')
            ->expectsOutputToContain('First Hero')
            ->expectsOutputToContain('Second Hero')
            ->assertSuccessful();

        $this->assertFalse($first->fresh()->is_active);
        $this->assertFalse($second->fresh()->is_active);
    }

    public function test_busts_the_storefront_home_cache_so_the_homepage_reflects_the_change_immediately(): void
    {
        $hero = $this->makeBanner(['title_en' => 'Cached Hero', 'is_active' => true]);

        // Prime the cache the same way HomeController::index() does,
        // capturing this hero banner as the active one — proves the
        // command's ->update() call fires Banner::booted()'s saved()
        // hook and busts the key, not just that a fresh request would
        // happen to see the change anyway.
        Cache::remember('storefront.home.data', now()->addMinutes(10), fn () => [
            'heroBanner' => Banner::active()->ofType(Banner::TYPE_HERO)->first(),
        ]);
        $this->assertNotNull(Cache::get('storefront.home.data')['heroBanner']);

        $this->artisan('banners:deactivate-hero')->assertSuccessful();

        $this->assertNull(Cache::get('storefront.home.data'));
        $this->assertFalse($hero->fresh()->is_active);
    }
}
