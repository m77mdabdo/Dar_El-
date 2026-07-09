<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Str;

class ProductDuplicator
{
    public function __construct(protected ImageUploadService $imageUploader)
    {
    }

    /**
     * Deep-copy a product: core fields, sizes, gallery images (actual files,
     * not just re-pointed paths, so deleting one copy never orphans the
     * other), and — if present — the options/values/variants tree. The
     * duplicate always starts as a draft regardless of the source's status,
     * so it never accidentally goes live, and its sku/variant skus are
     * cleared (kept as-is would violate the unique constraint on both
     * products.sku and product_variants.sku).
     */
    public function duplicate(Product $source): Product
    {
        $source->loadMissing(['sizes', 'images', 'options.values.images', 'variants.values']);

        $copy = Product::create([
            'category_id' => $source->category_id,
            'name_ar' => $source->name_ar.' (نسخة)',
            'name_en' => $source->name_en.' (Copy)',
            'slug' => Str::slug($source->name_en).'-copy-'.Str::random(4),
            'description_ar' => $source->description_ar,
            'description_en' => $source->description_en,
            'price' => $source->price,
            'compare_at_price' => $source->compare_at_price,
            'sku' => null,
            'badge' => $source->badge,
            'is_featured' => $source->is_featured,
            'status' => Product::STATUS_DRAFT,
            'is_active' => false,
        ]);

        if ($source->image_url) {
            $copy->update(['image_url' => $this->imageUploader->copy($source->image_url, "products/{$copy->id}")]);
        }

        foreach ($source->sizes as $size) {
            $copy->sizes()->create(['size' => $size->size, 'stock' => $size->stock]);
        }

        foreach ($source->images as $image) {
            $copy->images()->create([
                'path' => $this->imageUploader->copy($image->path, "products/{$copy->id}"),
                'sort_order' => $image->sort_order,
            ]);
        }

        $valueMap = [];

        foreach ($source->options as $option) {
            $newOption = $copy->options()->create([
                'name_ar' => $option->name_ar,
                'name_en' => $option->name_en,
                'sort_order' => $option->sort_order,
            ]);

            foreach ($option->values as $value) {
                $newValue = $newOption->values()->create([
                    'name_ar' => $value->name_ar,
                    'name_en' => $value->name_en,
                    'sort_order' => $value->sort_order,
                    'is_active' => $value->is_active,
                    'hex_color' => $value->hex_color,
                    'swatch_image' => $value->swatch_image
                        ? $this->imageUploader->copy($value->swatch_image, "products/{$copy->id}/options")
                        : null,
                ]);

                foreach ($value->images as $valueImage) {
                    $newValue->images()->create([
                        'path' => $this->imageUploader->copy($valueImage->path, "products/{$copy->id}/options"),
                        'sort_order' => $valueImage->sort_order,
                    ]);
                }

                $valueMap[$value->id] = $newValue->id;
            }
        }

        foreach ($source->variants as $variant) {
            $newVariant = $copy->variants()->create([
                'sku' => null,
                'barcode' => $variant->barcode,
                'price_override' => $variant->price_override,
                'sale_price' => $variant->sale_price,
                'stock' => $variant->stock,
                'weight' => $variant->weight,
                'low_stock_threshold' => $variant->low_stock_threshold,
                'image' => $variant->image
                    ? $this->imageUploader->copy($variant->image, "products/{$copy->id}/variants")
                    : null,
                'is_active' => $variant->is_active,
            ]);

            $newValueIds = $variant->values->map(fn ($value) => $valueMap[$value->id] ?? null)->filter()->values();

            if ($newValueIds->isNotEmpty()) {
                $newVariant->values()->attach($newValueIds);
            }
        }

        return $copy;
    }
}
