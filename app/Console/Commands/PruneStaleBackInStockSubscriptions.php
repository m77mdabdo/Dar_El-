<?php

namespace App\Console\Commands;

use App\Models\BackInStockSubscription;
use App\Models\Product;
use Illuminate\Console\Command;

class PruneStaleBackInStockSubscriptions extends Command
{
    protected $signature = 'back-in-stock:prune {--months=6 : Minimum subscription age, in months, to be eligible for pruning}';

    protected $description = 'Delete back-in-stock subscriptions old enough that they represent dead interest — the product was archived, or it has sat at zero stock the whole time with nobody resubscribing — so the table does not grow unbounded for discontinued products.';

    public function handle(): int
    {
        $months = (int) $this->option('months');
        $cutoff = now()->subMonths($months);

        $candidates = BackInStockSubscription::with('product.sizes')
            ->where('created_at', '<=', $cutoff)
            ->get();

        $prunable = $candidates->filter(function (BackInStockSubscription $subscription) {
            $product = $subscription->product;

            // The FK cascades on product deletion (see product_related's
            // same behavior), so this shouldn't normally happen — kept as a
            // defensive fallback rather than assumed impossible.
            if (! $product) {
                return true;
            }

            if ($product->status === Product::STATUS_ARCHIVED) {
                return true;
            }

            // Still genuinely out of stock after sitting unfulfilled for the
            // full threshold — a product that restocked at any point in that
            // window would already have fired notifySubscribers() and set
            // notified_at, or the subscriber would have resubscribed and
            // reset it (see BackInStockSubscriptionController::store()).
            // Only null-notified_at rows reach this branch for that reason.
            if ($subscription->notified_at === null) {
                $stock = $subscription->product_size_id
                    ? $product->sizes->firstWhere('id', $subscription->product_size_id)?->stock ?? 0
                    : $product->sizes->sum('stock');

                return $stock <= 0;
            }

            return false;
        });

        $ids = $prunable->pluck('id');
        BackInStockSubscription::whereIn('id', $ids)->delete();

        $this->info("{$ids->count()} stale back-in-stock subscription(s) pruned (checked ".$candidates->count()." older than {$months} month(s)).");

        return self::SUCCESS;
    }
}
