<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Faq;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    /**
     * Cache TTL for the storefront home-page and shared category-list
     * caches — a request within this window reuses the same computed
     * data; Product/Category writes bust the relevant key immediately
     * (see Product::booted()/Category::booted()) regardless of TTL, so
     * this is a ceiling on staleness, not the only way it clears.
     */
    private const CACHE_TTL_MINUTES = 10;

    public function index()
    {
        // Shared with ShopController — same query, same cache key, so a
        // category write only needs to bust one entry for both pages.
        $categories = Cache::remember('storefront.categories', now()->addMinutes(self::CACHE_TTL_MINUTES), fn () =>
            Category::where('is_active', true)->orderBy('sort_order')->get()
        );

        // None of these depend on request input, unlike ShopController's
        // filtered/sorted/paginated product listing (deliberately NOT
        // cached — the number of filter/sort/page combinations makes a
        // single cache key wrong and a per-combination key impractical).
        $homeData = Cache::remember('storefront.home.data', now()->addMinutes(self::CACHE_TTL_MINUTES), function () {
            return [
                'featuredProducts' => Product::with(['images', 'category', 'sizes', 'approvedReviews'])
                    ->where('is_active', true)
                    ->where('is_featured', true)
                    ->latest()
                    ->take(8)
                    ->get(),
                'latestPosts' => BlogPost::where('is_published', true)
                    ->latest('published_at')
                    ->take(3)
                    ->get(),
                'collections' => Collection::where('is_active', true)->orderBy('sort_order')->take(6)->get(),
                'offerBanners' => Banner::active()->ofType(Banner::TYPE_OFFER)->take(3)->get(),
                // The first active hero banner (by sort_order) an admin has
                // created replaces the default hero content below — see
                // home.blade.php. Deliberately opt-in: null (no admin ever
                // created one) falls back to the pre-existing hardcoded
                // copy + home_hero_image Setting, so a store that's never
                // touched the Hero Banners screen sees zero change.
                'heroBanner' => Banner::active()->ofType(Banner::TYPE_HERO)->first(),
                // Same opt-in pattern as heroBanner above: an empty
                // collection (no reviews ever marked featured) falls back
                // to the pre-existing hardcoded testimonial cards in
                // home.blade.php, so a store that's never used the
                // "Featured" filter on the Reviews screen sees zero change.
                'featuredReviews' => Review::approved()->featured()->latest()->take(6)->get(),
                // Same opt-in pattern again: an empty collection (no admin
                // has ever added a real FAQ) falls back to
                // Faq::fallbackList() — the exact 5 questions that used
                // to be hardcoded directly in this view.
                'faqs' => Faq::active()->orderBy('sort_order')->orderBy('id')->take(5)->get(),
                'trendingProducts' => $this->trendingProducts(),
            ];
        });

        $heroImage = Setting::get('home_hero_image', 'https://images.unsplash.com/photo-1682195721373-93bf6c181938?w=1600&q=80&auto=format&fit=crop');

        return view('home', [
            'featuredProducts' => $homeData['featuredProducts'],
            'categories' => $categories,
            'latestPosts' => $homeData['latestPosts'],
            'heroImage' => $heroImage,
            'heroBanner' => $homeData['heroBanner'],
            'collections' => $homeData['collections'],
            'offerBanners' => $homeData['offerBanners'],
            'featuredReviews' => $homeData['featuredReviews'],
            'faqs' => $homeData['faqs']->isNotEmpty()
                ? $homeData['faqs']->map(fn (Faq $faq) => ['q' => trans_field($faq, 'question'), 'a' => trans_field($faq, 'answer')])->all()
                : Faq::fallbackList(),
            'trendingProducts' => $homeData['trendingProducts'],
        ]);
    }

    /**
     * Real "trending" signal — highest quantity sold across non-cancelled
     * orders in the last 30 days — rather than a hardcoded or random list.
     * Resolves to an empty collection (section hidden by the view) on a
     * store with no order history yet.
     */
    protected function trendingProducts()
    {
        $trendingIds = OrderItem::whereHas('order', fn ($q) => $q
            ->where('created_at', '>=', now()->subDays(30))
            ->where('status', '!=', 'cancelled'))
            ->selectRaw('product_id, SUM(quantity) as qty')
            ->groupBy('product_id')
            ->orderByDesc('qty')
            ->take(8)
            ->pluck('product_id');

        if ($trendingIds->isEmpty()) {
            return collect();
        }

        return Product::with(['images', 'category', 'sizes', 'approvedReviews'])
            ->where('is_active', true)
            ->whereIn('id', $trendingIds)
            ->get()
            ->sortBy(fn ($product) => $trendingIds->search($product->id))
            ->values();
    }
}
