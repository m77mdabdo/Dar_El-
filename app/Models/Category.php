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
}
