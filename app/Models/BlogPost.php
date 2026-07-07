<?php

namespace App\Models;

use App\Services\ImageUploadService;
use Illuminate\Database\Eloquent\Model;

class BlogPost extends Model
{
    protected $fillable = [
        'title_ar', 'title_en', 'slug', 'excerpt_ar', 'excerpt_en',
        'body_ar', 'body_en', 'cover_image', 'is_published', 'published_at',
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
}
