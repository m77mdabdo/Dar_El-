<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Collection;
use App\Models\OrderItem;
use App\Models\Product;
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
                'trendingProducts' => $this->trendingProducts(),
            ];
        });

        $heroImage = Setting::get('home_hero_image', 'https://images.unsplash.com/photo-1682195721373-93bf6c181938?w=1600&q=80&auto=format&fit=crop');

        return view('home', [
            'featuredProducts' => $homeData['featuredProducts'],
            'categories' => $categories,
            'latestPosts' => $homeData['latestPosts'],
            'heroImage' => $heroImage,
            'collections' => $homeData['collections'],
            'offerBanners' => $homeData['offerBanners'],
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
