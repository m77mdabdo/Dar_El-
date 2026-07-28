<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Cross-product wishlist analytics — genuinely new. Per-customer wishlist
 * visibility already existed (Admin\CustomerController::wishlist()), but
 * nothing surfaced "which products get wishlisted most, store-wide" or
 * the actionable cross-reference with stock (a wishlisted-but-low/out-of-
 * stock product is a strong restock signal a shop owner can't currently
 * see anywhere). Shared by two sidebar entries — Marketing > Wishlist and
 * Reports > Wishlist — both point at this same controller rather than
 * duplicating the page, matching the Testimonials-reuses-Reviews pattern
 * from earlier this session. Gated by 'reports.wishlist', the permission
 * slug both of those sidebar entries were already pre-wired to.
 */
class WishlistAnalyticsController extends Controller
{
    public function index(Request $request): View
    {
        // whereHas() (an EXISTS subquery), not having('wishlists_count', '>', 0)
        // — the latter requires a real GROUP BY to be portable, and SQLite
        // (used in tests) rejects a HAVING clause with none, since
        // withCount()'s aggregate is a correlated subquery column, not a
        // grouped aggregate.
        $products = Product::query()
            ->with('category')
            ->whereHas('wishlists')
            ->withCount('wishlists')
            ->withSum('sizes as total_stock', 'stock')
            ->filterByStockStatus($request->stock_status)
            ->when(
                $request->sort === 'stock_asc',
                fn ($q) => $q->orderBy('total_stock'),
                fn ($q) => $q->orderByDesc('wishlists_count')
            )
            ->paginate(20)
            ->withQueryString();

        $totalWishlistAdds = Wishlist::count();
        $wishlistedLowStockCount = Product::query()->whereHas('wishlists')->filterByStockStatus('low_stock')->count();
        $wishlistedOutOfStockCount = Product::query()->whereHas('wishlists')->filterByStockStatus('out_of_stock')->count();

        return view('admin.wishlist-analytics.index', compact(
            'products', 'totalWishlistAdds', 'wishlistedLowStockCount', 'wishlistedOutOfStockCount'
        ));
    }
}
