<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    private const MAX_RESULTS = 8;

    /**
     * Lightweight live-search preview for the navbar. Deliberately NOT
     * cached — the same reasoning as ShopController's filtered listing:
     * stale cached results would show a product that's gone out of stock
     * or been deactivated since, which is worse than no caching at all.
     */
    public function live(Request $request): JsonResponse
    {
        $query = trim((string) $request->q);

        if (mb_strlen($query) < 2) {
            return response()->json(['results' => [], 'total' => 0, 'query' => $query]);
        }

        $matches = Product::where('is_active', true)->searchByName($query);

        $total = $matches->count();

        $results = $matches
            ->latest()
            ->take(self::MAX_RESULTS)
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => trans_field($product, 'name'),
                'price_formatted' => number_format($product->price).' EGP',
                'image' => $product->cover_thumb_src,
                'url' => route('shop.show', $product),
            ]);

        return response()->json([
            'results' => $results,
            'total' => $total,
            'query' => $query,
            'see_all_url' => route('shop.index', ['q' => $query]),
        ]);
    }
}
