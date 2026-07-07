<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductSize;
use App\Models\User;
use App\Notifications\ProductLowStock;
use App\Notifications\ProductOutOfStock;
use Illuminate\Support\Facades\Notification;

class StockAlertService
{
    /**
     * Notify admins when a size's stock crosses into low-stock or
     * out-of-stock territory. Only fires on the crossing itself (comparing
     * the stock level before and after the change) so admins aren't
     * re-notified on every subsequent order for an already-low item.
     */
    public function checkThreshold(Product $product, ProductSize $size, int $before, int $after): void
    {
        if ($before === $after) {
            return;
        }

        if ($after <= 0 && $before > 0) {
            Notification::send(User::admins(), new ProductOutOfStock($product, $size));

            return;
        }

        if ($after <= Product::LOW_STOCK_THRESHOLD && $before > Product::LOW_STOCK_THRESHOLD) {
            Notification::send(User::admins(), new ProductLowStock($product, $size));
        }
    }
}
