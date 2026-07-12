<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Collection;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Setting;

class HomeController extends Controller
{
    public function index()
    {
        $featuredProducts = Product::with(['images', 'category', 'sizes', 'approvedReviews'])
            ->where('is_active', true)
            ->where('is_featured', true)
            ->latest()
            ->take(8)
            ->get();

        $categories = Category::where('is_active', true)->orderBy('sort_order')->get();

        $latestPosts = BlogPost::where('is_published', true)
            ->latest('published_at')
            ->take(3)
            ->get();

        $heroImage = Setting::get('home_hero_image', 'https://images.unsplash.com/photo-1682195721373-93bf6c181938?w=1600&q=80&auto=format&fit=crop');

        $collections = Collection::where('is_active', true)->orderBy('sort_order')->take(6)->get();
        $offerBanners = Banner::active()->ofType(Banner::TYPE_OFFER)->take(3)->get();
        $trendingProducts = $this->trendingProducts();

        return view('home', compact('featuredProducts', 'categories', 'latestPosts', 'heroImage', 'collections', 'offerBanners', 'trendingProducts'));
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
