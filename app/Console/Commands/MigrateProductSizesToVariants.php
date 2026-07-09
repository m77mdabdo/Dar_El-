<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateProductSizesToVariants extends Command
{
    protected $signature = 'products:migrate-sizes-to-variants';

    protected $description = 'Copy each product\'s existing ProductSize rows into a "Size" option + values + variants, leaving product_sizes untouched.';

    public function handle(): int
    {
        $migrated = 0;
        $alreadyDone = 0;
        $skipped = 0;

        Product::with('sizes', 'options')->chunkById(50, function ($products) use (&$migrated, &$alreadyDone, &$skipped) {
            foreach ($products as $product) {
                if ($product->sizes->isEmpty()) {
                    $skipped++;

                    continue;
                }

                if ($product->options->contains(fn ($option) => $option->name_en === 'Size')) {
                    $alreadyDone++;

                    continue;
                }

                DB::transaction(function () use ($product) {
                    $option = $product->options()->create([
                        'name_ar' => 'المقاس',
                        'name_en' => 'Size',
                        'sort_order' => 0,
                    ]);

                    foreach ($product->sizes as $size) {
                        $value = $option->values()->create([
                            'name_ar' => $size->size,
                            'name_en' => $size->size,
                            'sort_order' => $size->id,
                            'is_active' => true,
                        ]);

                        $variant = $product->variants()->create([
                            'sku' => null,
                            'stock' => $size->stock,
                            'is_active' => true,
                        ]);

                        $variant->values()->attach($value->id);
                    }
                });

                $migrated++;
            }
        });

        $this->info("Migrated {$migrated} product(s). {$alreadyDone} already had a Size option. {$skipped} skipped (no sizes).");

        return self::SUCCESS;
    }
}
