<?php

namespace App\Models;

use App\Services\ImageUploadService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Banner extends Model
{
    use HasFactory;

    const TYPE_HERO = 'hero';

    const TYPE_OFFER = 'offer';

    const TYPE_COLLECTION = 'collection';

    const TYPE_CATEGORY = 'category';

    protected $fillable = [
        'title_ar', 'title_en', 'subtitle_ar', 'subtitle_en',
        'image', 'link_url', 'type', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Banner $banner) {
            app(ImageUploadService::class)->delete($banner->image);
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type)->orderBy('sort_order');
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image) {
            return null;
        }

        return Str::startsWith($this->image, ['http://', 'https://']) ? $this->image : asset('storage/'.$this->image);
    }
}
