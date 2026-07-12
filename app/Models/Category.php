<?php

namespace App\Models;

use App\Services\ImageUploadService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_ar', 'name_en', 'slug', 'description_ar', 'description_en',
        'image', 'is_active', 'sort_order',
        'meta_title_ar', 'meta_title_en', 'meta_description_ar', 'meta_description_en',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Category $category) {
            app(ImageUploadService::class)->delete($category->image);
        });
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Falls back to the category's own name when no explicit SEO override
     * has been set for that locale — mirrors Product::seoTitle().
     */
    public function seoTitle(string $locale): string
    {
        $override = $this->{"meta_title_{$locale}"};

        return $override ?: $this->{"name_{$locale}"};
    }

    public function seoDescription(string $locale): string
    {
        $override = $this->{"meta_description_{$locale}"};

        return $override ?: (string) $this->{"description_{$locale}"};
    }
}
