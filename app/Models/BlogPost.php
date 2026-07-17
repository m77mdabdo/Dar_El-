<?php

namespace App\Models;

use App\Services\ImageUploadService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class BlogPost extends Model
{
    protected $fillable = [
        'title_ar', 'title_en', 'slug', 'excerpt_ar', 'excerpt_en',
        'body_ar', 'body_en', 'cover_image', 'author_name', 'category',
        'meta_title_ar', 'meta_title_en', 'meta_description_ar', 'meta_description_en',
        'is_published', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (BlogPost $post) {
            app(ImageUploadService::class)->delete($post->cover_image);
        });

        // Not an arrow function: Cache::forget() returns false when the key
        // was already absent, and Laravel's event dispatcher treats a
        // listener returning literal false as a signal to halt all further
        // listeners for that event — see the identical comment on
        // Product::booted() for the full explanation.
        static::saved(function () {
            Cache::forget('sitemap.xml');
        });
        static::deleted(function () {
            Cache::forget('sitemap.xml');
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function comments(): HasMany
    {
        return $this->hasMany(BlogComment::class);
    }

    public function approvedComments(): HasMany
    {
        return $this->hasMany(BlogComment::class)->where('status', 'approved');
    }

    public function getCommentsCountAttribute(): int
    {
        return $this->approvedComments->count();
    }

    /**
     * Falls back to the post's own title/excerpt when no explicit SEO
     * override has been set for that locale — mirrors Product::seoTitle()
     * and Category::seoTitle().
     */
    public function seoTitle(string $locale): string
    {
        $override = $this->{"meta_title_{$locale}"};

        return $override ?: $this->{"title_{$locale}"};
    }

    public function seoDescription(string $locale): string
    {
        $override = $this->{"meta_description_{$locale}"};

        return $override ?: (string) $this->{"excerpt_{$locale}"};
    }
}
