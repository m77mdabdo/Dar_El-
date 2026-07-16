<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        // Deliberately NOT cached: the filter/sort/pagination combinations
        // this query can take make a single cache key wrong (would serve
        // one filter's results for another) and a per-combination key
        // impractical.
        $products = Product::with(['images', 'category', 'sizes', 'approvedReviews'])
            ->where('is_active', true)
            ->when($request->category, fn ($q) => $q->whereHas('category', fn ($c) => $c->where('slug', $request->category)))
            ->when($request->collection, fn ($q) => $q->whereHas('collections', fn ($c) => $c->where('slug', $request->collection)))
            ->searchByName($request->q)
            ->when($request->min_price, fn ($q) => $q->where('price', '>=', (int) $request->min_price))
            ->when($request->max_price, fn ($q) => $q->where('price', '<=', (int) $request->max_price))
            ->when($request->size, fn ($q) => $q->whereHas('sizes', fn ($s) => $s->where('size', $request->size)))
            ->when($request->sort === 'price_asc', fn ($q) => $q->orderBy('price'))
            ->when($request->sort === 'price_desc', fn ($q) => $q->orderByDesc('price'))
            ->when(! $request->sort, fn ($q) => $q->latest())
            ->paginate(12)
            ->withQueryString();

        // Shared with HomeController — same query, same cache key.
        $categories = Cache::remember('storefront.categories', now()->addMinutes(10), fn () =>
            Category::where('is_active', true)->orderBy('sort_order')->get()
        );

        // The storefront's live stock system is ProductSize (used throughout
        // cart/checkout/product-detail) — NOT the newer ProductVariant engine,
        // which exists in the schema but isn't wired into the storefront yet.
        // Only sizes with in-stock, active products are offered as filter
        // options, so the filter never leads a customer to a dead end.
        $availableSizes = ProductSize::where('stock', '>', 0)
            ->whereHas('product', fn ($q) => $q->where('is_active', true))
            ->distinct()
            ->orderBy('size')
            ->pluck('size');

        $heroImage = Setting::get('shop_hero_image', 'https://images.unsplash.com/photo-1532370436137-d9aaea5dab36?w=1600&q=80&auto=format&fit=crop');

        return view('shop.index', compact('products', 'categories', 'availableSizes', 'heroImage'));
    }

    public function show(Product $product)
    {
        abort_unless($product->is_active, 404);

        $product->load(['images', 'sizes', 'category', 'brand', 'approvedReviews.images', 'approvedReviews.user']);

        $relatedProducts = Product::with(['images', 'sizes', 'approvedReviews'])
            ->where('is_active', true)
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->take(4)
            ->get();

        $recommendedProducts = $product->brand_id
            ? Product::with(['images', 'sizes', 'approvedReviews'])
                ->where('is_active', true)
                ->where('brand_id', $product->brand_id)
                ->where('id', '!=', $product->id)
                ->inRandomOrder()
                ->take(4)
                ->get()
            : collect();

        $userReview = auth()->check()
            ? $product->reviews()->where('user_id', auth()->id())->first()
            : null;

        return view('shop.show', compact('product', 'relatedProducts', 'recommendedProducts', 'userReview'));
    }
}
