<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Database\Seeder;

/**
 * Realistic wishlist activity from the same demo customer pool used by
 * DemoReviewSeeder/DemoOrderSeeder, so DashboardController's "top
 * wishlisted products" widget has real data. Wishlist has a DB-level
 * unique(user_id, product_id) constraint, so firstOrCreate here is both
 * idempotent and collision-safe.
 */
class DemoWishlistSeeder extends Seeder
{
    public function run(): void
    {
        $customers = User::where('email', 'like', 'demo.reviewer%@example.com')->orderBy('id')->get();
        $products = Product::orderBy('id')->get();
        $productCount = $products->count();

        if ($customers->isEmpty() || $productCount === 0) {
            $this->command?->warn('Skipping wishlists — no demo customers or products found yet.');

            return;
        }

        // Deterministic selection (customer id, not randomness) so a second
        // run always re-derives the exact same set of pairs and creates
        // zero new rows — each customer gets a fixed 4-12 products picked
        // by a stable rotating offset rather than Collection::random().
        foreach ($customers as $customer) {
            $pickCount = min($productCount, 4 + ($customer->id % 9));

            for ($i = 0; $i < $pickCount; $i++) {
                $product = $products[($customer->id * 7 + $i * 13) % $productCount];

                Wishlist::firstOrCreate([
                    'user_id' => $customer->id,
                    'product_id' => $product->id,
                ]);
            }
        }
    }
}
