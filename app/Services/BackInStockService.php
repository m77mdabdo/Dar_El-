<?php

namespace App\Services;

use App\Mail\ProductBackInStockMail;
use App\Models\BackInStockSubscription;
use App\Models\Product;
use App\Models\ProductSize;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class BackInStockService
{
    public function __construct(protected PushNotificationService $push) {}

    /**
     * Notify subscribers when a size's stock crosses from 0 (or below) to
     * positive. Only fires on the crossing itself, same convention as
     * StockAlertService::checkThreshold() — a size going from 3 to 5 units
     * doesn't re-notify anyone, only 0-to-positive does.
     *
     * Self-contained and non-throwing, same contract as checkThreshold():
     * callers (an admin stock edit, an order-cancellation restock) can call
     * this directly without their own try/catch — a notification failure
     * here must never break or roll back whatever action just restored the
     * stock.
     */
    public function checkAndNotify(Product $product, ProductSize $size, int $before, int $after): void
    {
        if ($before > 0 || $after <= 0) {
            return;
        }

        $this->notifySubscribers($product, $size->id, $size);

        // A product with 0 or 1 size rows has no meaningful size choice in
        // the storefront UI — its "notify me" signup is whole-product-level
        // (product_size_id = null), and that one size row's own stock is,
        // in practice, the product's total stock.
        if ($product->sizes()->count() <= 1) {
            $this->notifySubscribers($product, null, $size);
        }
    }

    protected function notifySubscribers(Product $product, ?int $productSizeId, ProductSize $size): void
    {
        $subscriptions = BackInStockSubscription::where('product_id', $product->id)
            ->where('product_size_id', $productSizeId)
            ->whereNull('notified_at')
            ->get();

        foreach ($subscriptions as $subscription) {
            try {
                Mail::to($subscription->email)->queue(new ProductBackInStockMail($subscription, $product, $size));

                // Marked immediately after a successful queue dispatch (not
                // after the email actually sends) so a second stock
                // fluctuation before the queued job runs can't result in a
                // duplicate send.
                $subscription->update(['notified_at' => now()]);
            } catch (Throwable $e) {
                Log::error('Back-in-stock notification failed', [
                    'subscription_id' => $subscription->id,
                    'product_id' => $product->id,
                    'product_size_id' => $productSizeId,
                    'error' => $e->getMessage(),
                ]);
            }

            // A separate, independent send from the email above — its own
            // try/catch already lives inside PushNotificationService, so a
            // push failure (or simply not being opted in, the common case)
            // can never affect the email that was just queued, and vice versa.
            if ($subscription->push_subscription_id) {
                $subscription->loadMissing('pushSubscription');

                if ($subscription->pushSubscription) {
                    $this->push->sendToSubscription(
                        $subscription->pushSubscription,
                        trans_field($product, 'name'),
                        __("It's back in stock — grab it before it sells out again."),
                        route('shop.show', $product)
                    );
                }
            }
        }
    }
}
