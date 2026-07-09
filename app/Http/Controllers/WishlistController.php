<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Wishlist;
use App\Services\CartService;
use App\Services\CartTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WishlistController extends Controller
{
    public function __construct(protected CartService $cart, protected CartTrackingService $cartTracking) {}

    public function index(Request $request): View
    {
        $wishlists = $request->user()->wishlists()
            ->with(['product.images', 'product.sizes', 'product.category'])
            ->latest()
            ->get()
            ->filter(fn (Wishlist $w) => $w->product !== null);

        return view('wishlist.index', ['wishlists' => $wishlists]);
    }

    public function store(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        $request->user()->wishlists()->firstOrCreate(['product_id' => $product->id]);

        $count = $request->user()->wishlists()->count();

        if ($request->wantsJson()) {
            return response()->json(['in_wishlist' => true, 'count' => $count]);
        }

        return back()->with('status', __('Added to wishlist.'));
    }

    public function destroy(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        $request->user()->wishlists()->where('product_id', $product->id)->delete();

        $count = $request->user()->wishlists()->count();

        if ($request->wantsJson()) {
            return response()->json(['in_wishlist' => false, 'count' => $count]);
        }

        return back()->with('status', __('Removed from wishlist.'));
    }

    public function moveToCart(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'size' => ['required', 'string'],
        ]);

        try {
            $this->cart->add($product, $validated['size'], 1);
        } catch (\RuntimeException $e) {
            if ($request->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }

        $this->cartTracking->sync($request->user(), $this->cart);

        $request->user()->wishlists()->where('product_id', $product->id)->delete();

        $wishlistCount = $request->user()->wishlists()->count();

        if ($request->wantsJson()) {
            return response()->json([
                'cart_count' => $this->cart->count(),
                'wishlist_count' => $wishlistCount,
            ]);
        }

        return back()->with('status', __('Moved to cart.'));
    }
}
