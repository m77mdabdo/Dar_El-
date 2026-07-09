<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ProductOptionValue extends Model
{
    protected $fillable = [
        'product_option_id', 'name_ar', 'name_en', 'sort_order',
        'is_active', 'hex_color', 'swatch_image',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(ProductOption::class, 'product_option_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductOptionValueImage::class)->orderBy('sort_order');
    }

    public function variants(): BelongsToMany
    {
        return $this->belongsToMany(ProductVariant::class, 'product_variant_option_value');
    }

    public function getSwatchImageUrlAttribute(): ?string
    {
        if (! $this->swatch_image) {
            return null;
        }

        return Str::startsWith($this->swatch_image, ['http://', 'https://'])
            ? $this->swatch_image
            : asset('storage/'.$this->swatch_image);
    }
}
