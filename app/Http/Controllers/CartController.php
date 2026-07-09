<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CartService;
use App\Services\CartTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(protected CartService $cart, protected CartTrackingService $cartTracking) {}

    public function index(): View
    {
        $items = $this->cart->items();

        return view('cart.index', [
            'items' => $items,
            'subtotal' => $this->cart->subtotal(),
            'discount' => $this->cart->discount(),
            'coupon' => $this->cart->appliedCoupon(),
            'hasStockIssues' => collect($items)->contains('exceeds_stock', true),
        ]);
    }

    public function add(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'size' => ['required', 'string'],
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        try {
            $this->cart->add($product, $validated['size'], $validated['quantity']);
        } catch (\RuntimeException $e) {
            if ($request->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }

        if ($request->user()) {
            $this->cartTracking->sync($request->user(), $this->cart);
        }

        if ($request->wantsJson()) {
            return $this->cartJson();
        }

        return back()->with('status', 'Added to cart.');
    }

    public function update(Request $request, string $key): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:20'],
        ]);

        try {
            $this->cart->update($key, $validated['quantity']);
        } catch (\RuntimeException $e) {
            if ($request->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }

        if ($request->user()) {
            $this->cartTracking->sync($request->user(), $this->cart);
        }

        return $request->wantsJson() ? $this->cartJson() : back();
    }

    public function remove(Request $request, string $key): RedirectResponse|JsonResponse
    {
        $this->cart->remove($key);

        if ($request->user()) {
            $this->cartTracking->sync($request->user(), $this->cart);
        }

        return $request->wantsJson() ? $this->cartJson() : back();
    }

    public function applyCoupon(Request $request): RedirectResponse
    {
        $validated = $request->validate(['code' => ['required', 'string']]);

        try {
            $this->cart->applyCoupon($validated['code']);
        } catch (\Throwable $e) {
            return back()->with('error', 'Invalid or expired coupon code.');
        }

        return back()->with('status', 'Coupon applied.');
    }

    public function removeCoupon(): RedirectResponse
    {
        $this->cart->removeCoupon();

        return back();
    }

    protected function cartJson(): JsonResponse
    {
        $items = $this->cart->items();
        $total = $this->cart->subtotal() - $this->cart->discount();

        return response()->json([
            'count' => $this->cart->count(),
            'total_formatted' => number_format($total).' EGP',
            'html' => view('partials.cart-drawer-items', ['items' => $items])->render(),
        ]);
    }
}
