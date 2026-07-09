<?php

namespace App\Models;

use App\Services\ImageUploadService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductOptionValueImage extends Model
{
    protected $fillable = ['product_option_value_id', 'path', 'sort_order'];

    protected static function booted(): void
    {
        static::deleting(function (ProductOptionValueImage $image) {
            app(ImageUploadService::class)->delete($image->path);
        });
    }

    public function value(): BelongsTo
    {
        return $this->belongsTo(ProductOptionValue::class, 'product_option_value_id');
    }
}
