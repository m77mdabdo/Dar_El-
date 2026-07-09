<?php

namespace App\Models;

use App\Services\ImageUploadService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id', 'sku', 'barcode', 'price_override', 'sale_price', 'stock',
        'weight', 'low_stock_threshold', 'image', 'is_active',
    ];

    protected static function booted(): void
    {
        static::deleting(function (ProductVariant $variant) {
            if ($variant->image) {
                app(ImageUploadService::class)->delete($variant->image);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'weight' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function values(): BelongsToMany
    {
        return $this->belongsToMany(ProductOptionValue::class, 'product_variant_option_value');
    }

    public function getEffectivePriceAttribute(): int
    {
        return $this->sale_price ?? $this->price_override ?? $this->product->price;
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image) {
            return null;
        }

        return Str::startsWith($this->image, ['http://', 'https://'])
            ? $this->image
            : asset('storage/'.$this->image);
    }

    /**
     * Bilingual "Burgundy / M"-style label built from this variant's option
     * values, ordered by each value's parent option's sort_order.
     */
    public function label(?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $field = $locale === 'ar' ? 'name_ar' : 'name_en';

        return $this->values
            ->sortBy(fn (ProductOptionValue $value) => $value->option->sort_order ?? 0)
            ->map(fn (ProductOptionValue $value) => $value->{$field})
            ->implode(' / ');
    }

    public function stockStatus(): array
    {
        $threshold = $this->low_stock_threshold ?? Product::LOW_STOCK_THRESHOLD;
        $stock = $this->stock;

        return match (true) {
            $stock <= 0 => ['status' => 'out_of_stock', 'label' => __('Out of Stock'), 'stock' => 0],
            $stock <= $threshold => ['status' => 'low_stock', 'label' => __('Only :count left', ['count' => $stock]), 'stock' => $stock],
            default => ['status' => 'in_stock', 'label' => __('In Stock'), 'stock' => $stock],
        };
    }
}
