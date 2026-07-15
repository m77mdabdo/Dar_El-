<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductSize;
use App\Models\User;
use App\Notifications\ProductLowStock;
use App\Notifications\ProductOutOfStock;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class StockAlertService
{
    /**
     * Notify admins when a size's stock crosses into low-stock or
     * out-of-stock territory. Only fires on the crossing itself (comparing
     * the stock level before and after the change) so admins aren't
     * re-notified on every subsequent order for an already-low item.
     *
     * Called from inside CheckoutController's order-creation DB::transaction,
     * so a notification failure here must never propagate — that would roll
     * back an entire valid, already-stock-checked customer order over a
     * failed admin alert. Same log-and-swallow philosophy as
     * CheckoutController::dispatchSafely(), inlined here since this runs
     * mid-transaction rather than in the post-commit dispatch block.
     */
    public function checkThreshold(Product $product, ProductSize $size, int $before, int $after): void
    {
        if ($before === $after) {
            return;
        }

        try {
            if ($after <= 0 && $before > 0) {
                Notification::send(User::admins(), new ProductOutOfStock($product, $size));

                return;
            }

            if ($after <= Product::LOW_STOCK_THRESHOLD && $before > Product::LOW_STOCK_THRESHOLD) {
                Notification::send(User::admins(), new ProductLowStock($product, $size));
            }
        } catch (Throwable $e) {
            Log::error('Stock alert notification failed (order still proceeds)', [
                'product_id' => $product->id,
                'product_size_id' => $size->id,
                'before' => $before,
                'after' => $after,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
