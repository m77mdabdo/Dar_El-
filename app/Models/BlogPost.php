<?php

namespace App\Models;

use App\Services\ImageUploadService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}
