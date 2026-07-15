<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;

class ProductDeleter
{
    /**
     * Non-terminal order statuses — a product referenced by an order still
     * in one of these blocks deletion; delivered/cancelled orders are
     * historically closed (their own snapshot columns already preserve
     * the record) and don't need the product to still exist.
     */
    private const BLOCKING_ORDER_STATUSES = ['pending', 'processing', 'shipped'];

    /**
     * Why a product can't be deleted right now, or null if it can.
     * order_items/cart_items both nullOnDelete() at the DB level, so
     * nothing would actually crash without this check — but deleting a
     * product a customer currently has in their cart mid-checkout, or
     * that's on an order still being fulfilled, silently pulls inventory
     * out from under an in-flight purchase.
     */
    public function blockingReason(Product $product): ?string
    {
        $pendingOrderCount = Order::whereIn('status', self::BLOCKING_ORDER_STATUSES)
            ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
            ->count();

        if ($pendingOrderCount > 0) {
            return __('products.cannot_delete_has_pending_orders', ['count' => $pendingOrderCount]);
        }

        $activeCartCount = Cart::open()
            ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
            ->count();

        if ($activeCartCount > 0) {
            return __('products.cannot_delete_in_active_carts', ['count' => $activeCartCount]);
        }

        return null;
    }

    /**
     * Delete files explicitly before the model delete: the FK cascade removes
     * the rows at the DB level without firing Eloquent's deleting event, so
     * images would otherwise orphan on disk.
     */
    public function delete(Product $product): void
    {
        $product->images->each->delete();
        $product->delete();
    }
}
