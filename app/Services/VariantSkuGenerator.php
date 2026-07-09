<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductOptionValue;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class VariantSkuGenerator
{
    /**
     * Builds a "{prefix}-{VALUE}-{VALUE}"-style SKU (e.g. TSHIRT-RED-M) from
     * the product's sku_prefix and a variant's option values, ordered by
     * each value's parent option's sort_order for a stable result. Returns
     * null when no prefix is set, so existing "blank SKU, filled in
     * manually" behavior is unchanged by default. Appends "-2", "-3", etc.
     * on a collision against $existingSkus (checked globally: sku is unique
     * across all products, not just scoped to one).
     *
     * @param  Collection<int, ProductOptionValue>  $values
     * @param  array<string>  $existingSkus
     */
    public function build(Product $product, Collection $values, array $existingSkus): ?string
    {
        if (! $product->sku_prefix) {
            return null;
        }

        $slug = $values
            ->sortBy(fn (ProductOptionValue $value) => $value->option->sort_order ?? 0)
            ->map(fn (ProductOptionValue $value) => Str::upper(Str::slug($value->name_en, '')))
            ->implode('-');

        $base = Str::upper($product->sku_prefix).($slug ? "-{$slug}" : '');
        $sku = $base;
        $suffix = 2;

        while (in_array($sku, $existingSkus, true)) {
            $sku = "{$base}-{$suffix}";
            $suffix++;
        }

        return $sku;
    }
}
