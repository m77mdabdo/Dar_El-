<?php

namespace App\Models;

use App\Services\ImageUploadService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewImage extends Model
{
    protected $fillable = ['review_id', 'path', 'sort_order'];

    protected static function booted(): void
    {
        static::deleting(function (ReviewImage $image) {
            app(ImageUploadService::class)->delete($image->path);
        });
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }
}
