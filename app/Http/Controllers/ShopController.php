<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with(['images', 'category', 'sizes', 'approvedReviews'])
            ->where('is_active', true)
            ->when($request->category, fn ($q) => $q->whereHas('category', fn ($c) => $c->where('slug', $request->category)))
            ->when($request->min_price, fn ($q) => $q->where('price', '>=', (int) $request->min_price))
            ->when($request->max_price, fn ($q) => $q->where('price', '<=', (int) $request->max_price))
            ->when($request->sort === 'price_asc', fn ($q) => $q->orderBy('price'))
            ->when($request->sort === 'price_desc', fn ($q) => $q->orderByDesc('price'))
            ->when(! $request->sort, fn ($q) => $q->latest())
            ->paginate(12)
            ->withQueryString();

        $categories = Category::where('is_active', true)->orderBy('sort_order')->get();

        $heroImage = Setting::get('shop_hero_image', 'https://images.unsplash.com/photo-1532370436137-d9aaea5dab36?w=1600&q=80&auto=format&fit=crop');

        return view('shop.index', compact('products', 'categories', 'heroImage'));
    }

    public function show(Product $product)
    {
        abort_unless($product->is_active, 404);

        $product->load(['images', 'sizes', 'category', 'approvedReviews']);

        $relatedProducts = Product::with(['images', 'sizes', 'approvedReviews'])
            ->where('is_active', true)
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->take(4)
            ->get();

        return view('shop.show', compact('product', 'relatedProducts'));
    }
}
