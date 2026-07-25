<?php

namespace App\Models;

use App\Services\ImageUploadService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
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
        'image', 'link_url', 'cta_text_ar', 'cta_text_en', 'type', 'is_active', 'sort_order',
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

        // Busts the storefront home-page cache (HomeController::index(),
        // which reads the active hero banner) on every write, regardless
        // of which admin action performs it — single CRUD, toggle-active,
        // or reorder.
        //
        // These must NOT be arrow functions — see Product::booted() for
        // why: Cache::forget() returning false would silently halt any
        // later saved()/deleted() listener Laravel's dispatcher runs for
        // this model.
        static::saved(function () {
            Cache::forget('storefront.home.data');
        });
        static::deleted(function () {
            Cache::forget('storefront.home.data');
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

    /**
     * Smaller thumbnail variant for admin list views — mirrors
     * Category::getImageThumbAttribute(). Falls back to the full-size
     * image for externally-hosted (http/https) banners, which have no
     * generated thumbnail.
     */
    public function getImageThumbAttribute(): ?string
    {
        if (! $this->image) {
            return null;
        }

        return Str::startsWith($this->image, ['http://', 'https://'])
            ? $this->image
            : app(ImageUploadService::class)->thumbnailUrl($this->image);
    }
}
