<?php

namespace App\Models;

use App\Services\ImageUploadService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    const LOW_STOCK_THRESHOLD = 5;

    protected $fillable = [
        'category_id', 'name_ar', 'name_en', 'slug', 'description_ar', 'description_en',
        'price', 'compare_at_price', 'sku', 'image_url', 'badge', 'is_active', 'is_featured',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Product $product) {
            app(ImageUploadService::class)->delete($product->image_url);
        });
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function sizes(): HasMany
    {
        return $this->hasMany(ProductSize::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('is_approved', true);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function totalStock(): int
    {
        return $this->sizes->sum('stock');
    }

    /**
     * Filter products by aggregate stock status using a correlated
     * subquery rather than GROUP BY/HAVING on a withSum() alias — the
     * latter trips MySQL's ONLY_FULL_GROUP_BY mode (and behaves
     * inconsistently vs SQLite), whereas a scalar subquery in WHERE is
     * portable across both and needs no grouping at all.
     */
    public function scopeFilterByStockStatus($query, ?string $status)
    {
        $stockExpr = '(select coalesce(sum(stock), 0) from product_sizes where product_sizes.product_id = products.id)';

        return match ($status) {
            'out_of_stock' => $query->whereRaw("{$stockExpr} <= 0"),
            'low_stock' => $query->whereRaw("{$stockExpr} > 0")->whereRaw("{$stockExpr} <= ?", [self::LOW_STOCK_THRESHOLD]),
            'in_stock' => $query->whereRaw("{$stockExpr} > ?", [self::LOW_STOCK_THRESHOLD]),
            default => $query,
        };
    }

    public function stockForSize(?string $size): int
    {
        if ($size === null) {
            return $this->totalStock();
        }

        return $this->sizes->firstWhere('size', $size)?->stock ?? 0;
    }

    /**
     * Stock status for a given quantity (defaults to the product's total
     * stock across sizes). Used to render "In Stock" / "Only X left" /
     * "Out of Stock" consistently across cards, modal, and PDP.
     *
     * @return array{status: string, label: string, stock: int}
     */
    public function stockStatus(?int $stock = null): array
    {
        $stock ??= $this->totalStock();

        return match (true) {
            $stock <= 0 => ['status' => 'out_of_stock', 'label' => __('Out of Stock'), 'stock' => 0],
            $stock <= self::LOW_STOCK_THRESHOLD => ['status' => 'low_stock', 'label' => __('Only :count left', ['count' => $stock]), 'stock' => $stock],
            default => ['status' => 'in_stock', 'label' => __('In Stock'), 'stock' => $stock],
        };
    }

    /**
     * Resolve the cover photo to display: the dedicated cover image if set
     * (local path or legacy full URL), otherwise the first gallery image.
     */
    public function getCoverImageSrcAttribute(): ?string
    {
        $path = $this->image_url ?: $this->images->first()?->path;

        if (! $path) {
            return null;
        }

        return Str::startsWith($path, ['http://', 'https://']) ? $path : asset('storage/'.$path);
    }

    public function getAverageRatingAttribute(): float
    {
        return round($this->approvedReviews->avg('rating') ?? 0, 1);
    }

    public function getReviewsCountAttribute(): int
    {
        return $this->approvedReviews->count();
    }
}
