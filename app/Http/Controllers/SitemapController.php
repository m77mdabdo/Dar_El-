<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    /**
     * Dynamic sitemap.xml, generated from live data rather than a static
     * file. Cached for an hour — reasonably fresh (a new product shows up
     * same-day) without regenerating on every crawler hit.
     */
    public function index(): Response
    {
        $xml = Cache::remember('sitemap.xml', now()->addHour(), fn () => $this->buildXml());

        return response($xml, 200)->header('Content-Type', 'text/xml; charset=UTF-8');
    }

    protected function buildXml(): string
    {
        $urls = [
            ['loc' => route('home'), 'lastmod' => now(), 'changefreq' => 'daily', 'priority' => '1.0'],
            ['loc' => route('shop.index'), 'lastmod' => now(), 'changefreq' => 'daily', 'priority' => '0.9'],
            ['loc' => route('about'), 'lastmod' => now(), 'changefreq' => 'monthly', 'priority' => '0.5'],
            ['loc' => route('contact.show'), 'lastmod' => now(), 'changefreq' => 'monthly', 'priority' => '0.5'],
            ['loc' => route('return-policy'), 'lastmod' => now(), 'changefreq' => 'monthly', 'priority' => '0.4'],
        ];

        foreach (Product::where('is_active', true)->select(['id', 'slug', 'updated_at'])->get() as $product) {
            $urls[] = [
                'loc' => route('shop.show', $product),
                'lastmod' => $product->updated_at,
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        }

        // A category has no dedicated page of its own — /shop?category={slug}
        // is the only URL that represents it, and ShopController's canonical
        // logic (see canonicalShopUrl()) treats a category-only filter as a
        // real, distinct, indexable view rather than collapsing it into the
        // plain /shop page the way every other filter/sort/search
        // combination does.
        foreach (Category::where('is_active', true)->select(['id', 'slug', 'updated_at'])->get() as $category) {
            $urls[] = [
                'loc' => route('shop.index', ['category' => $category->slug]),
                'lastmod' => $category->updated_at,
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ];
        }

        foreach (BlogPost::where('is_published', true)->select(['id', 'slug', 'updated_at'])->get() as $post) {
            $urls[] = [
                'loc' => route('blog.show', $post),
                'lastmod' => $post->updated_at,
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ];
        }

        $body = view('sitemap.index', ['urls' => $urls])->render();

        // The XML declaration is built here rather than as literal text at
        // the top of the Blade file — "<?xml" at the very start of a
        // compiled view risks being misread as a PHP short-open-tag on any
        // server where short_open_tag is enabled, corrupting the output.
        return '<?xml version="1.0" encoding="UTF-8"?>'."\n".$body;
    }
}
