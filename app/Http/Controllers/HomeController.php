<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Category;
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

        return view('home', compact('featuredProducts', 'categories', 'latestPosts', 'heroImage'));
    }
}
