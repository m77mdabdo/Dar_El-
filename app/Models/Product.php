<?php

namespace App\Models;

use App\Services\ImageUploadService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    const LOW_STOCK_THRESHOLD = 5;

    const STATUS_DRAFT = 'draft';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_PUBLISHED = 'published';
    const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'category_id', 'brand_id', 'name_ar', 'name_en', 'slug', 'description_ar', 'description_en',
        'price', 'compare_at_price', 'sku', 'barcode', 'image_url', 'badge', 'is_active', 'is_featured',
        'status', 'scheduled_publish_at', 'published_at',
        'meta_title_ar', 'meta_title_en', 'meta_description_ar', 'meta_description_en',
        'sku_prefix', 'default_stock', 'default_low_stock_threshold', 'weight', 'dimensions',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Product $product) {
            app(ImageUploadService::class)->delete($product->image_url);
        });

        // Busts the storefront home-page cache (HomeController::index())
        // on every product write, regardless of which code path performs
        // it — single CRUD, bulk publish/archive/delete, anything. saved()
        // fires after both create and update.
        //
        // These must NOT be arrow functions: Cache::forget() returns false
        // when the key was already absent, and Laravel's event dispatcher
        // treats a listener returning literal false as a signal to halt all
        // further listeners for that event — an arrow function silently
        // leaks that boolean as its return value, which would stop the
        // sitemap.xml listeners below from ever running whenever this cache
        // key happened to already be empty.
        static::saved(function () {
            Cache::forget('storefront.home.data');
        });
        static::deleted(function () {
            Cache::forget('storefront.home.data');
        });

        static::saved(function () {
            Cache::forget('sitemap.xml');
        });
        static::deleted(function () {
            Cache::forget('sitemap.xml');
        });
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'scheduled_publish_at' => 'datetime',
            'published_at' => 'datetime',
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

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class, 'product_collection');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function sizes(): HasMany
    {
        return $this->hasMany(ProductSize::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class)->orderBy('sort_order');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('status', 'approved');
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function totalStock(): int
    {
        return $this->sizes->sum('stock');
    }

    public function scopeSearch($query, ?string $term)
    {
        if (! $term) {
            return $query;
        }

        return $query->where(fn ($q) => $q
            ->where('name_en', 'like', "%{$term}%")
            ->orWhere('name_ar', 'like', "%{$term}%")
            ->orWhere('sku', 'like', "%{$term}%")
        );
    }

    /**
     * Customer-facing search — name only (name_en/name_ar), never SKU, and
     * LIKE-escaped so a literal % or _ in a search term is matched literally
     * rather than acting as a SQL wildcard. Deliberately separate from the
     * admin-only scopeSearch() above (which also matches SKU and isn't
     * escaped) — shared by ShopController's filtered listing and the
     * navbar's live-search endpoint so the matching logic lives in one place.
     *
     * name_ar is compared after Arabic normalization (both sides — the
     * column via a raw SQL REPLACE() chain, the term via normalizeArabicTerm())
     * so common spelling variations customers actually type (hamza placement,
     * alef maksura vs yaa, taa marbuta vs haa, diacritics, tatweel) don't
     * cause an otherwise-matching product to be missed. name_en isn't
     * normalized — this is specifically an Arabic input-variation problem.
     */
    public function scopeSearchByName($query, ?string $term)
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);
        $normalizedTerm = static::normalizeArabicTerm($escaped);
        $normalizedNameArSql = static::normalizedNameArSql();

        return $query->where(function ($q) use ($escaped, $normalizedTerm, $normalizedNameArSql) {
            $q->where('name_en', 'like', "%{$escaped}%")
                ->orWhereRaw("{$normalizedNameArSql} LIKE ?", ["%{$normalizedTerm}%"]);
        });
    }

    /**
     * Single source of truth for Arabic spelling-variation normalization —
     * used to build both the raw SQL REPLACE() chain against the name_ar
     * column (normalizedNameArSql()) and the PHP-side term normalization
     * (normalizeArabicTerm()), so the two can never drift apart. Values are
     * plain literals (no quotes/backslashes), safe to embed directly in the
     * SQL built from this map.
     */
    protected static function arabicNormalizationMap(): array
    {
        return [
            // Hamza-bearing alef forms, and bare hamza, all fold to plain alef.
            'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ء' => 'ا',
            // Alef maksura -> yaa.
            'ى' => 'ي',
            // Taa marbuta -> haa.
            'ة' => 'ه',
            // Tashkeel: tanween (fath/damm/kasr), fatha, damma, kasra, shadda, sukun.
            "\u{064B}" => '', "\u{064C}" => '', "\u{064D}" => '',
            "\u{064E}" => '', "\u{064F}" => '', "\u{0650}" => '',
            "\u{0651}" => '', "\u{0652}" => '',
            // Tatweel/kashida.
            "\u{0640}" => '',
        ];
    }

    public static function normalizeArabicTerm(string $text): string
    {
        $text = strtr($text, static::arabicNormalizationMap());

        return trim(preg_replace('/\s+/', ' ', $text));
    }

    /**
     * A raw SQL expression normalizing the name_ar column the same way
     * normalizeArabicTerm() normalizes the search term. Built from chained
     * REPLACE() calls (not REGEXP_REPLACE()) specifically because this
     * project's tests run on SQLite while production runs on MySQL —
     * REPLACE() is the one substitution function both support identically.
     * Whitespace collapsing gets two REPLACE('  ',' ') passes rather than
     * regex, for the same portability reason; that only handles up to a
     * handful of consecutive spaces, unlike the term-side preg_replace,
     * which is an accepted tradeoff since stored product names aren't
     * expected to contain long runs of whitespace to begin with.
     */
    protected static function normalizedNameArSql(): string
    {
        $expr = 'name_ar';

        foreach (static::arabicNormalizationMap() as $search => $replace) {
            $expr = "REPLACE({$expr}, '{$search}', '{$replace}')";
        }

        return "REPLACE(REPLACE({$expr}, '  ', ' '), '  ', ' ')";
    }

    public function scopeOfStatus($query, ?string $status)
    {
        return $status ? $query->where('status', $status) : $query;
    }

    /**
     * The single place that derives is_active from the richer status
     * workflow — the storefront (ShopController) reads is_active directly,
     * so every write path (form save, bulk action, scheduled command) must
     * go through here rather than setting is_active itself.
     */
    public function applyStatus(string $status): void
    {
        $this->status = $status;
        $this->is_active = $status === self::STATUS_PUBLISHED;

        if ($status === self::STATUS_PUBLISHED && ! $this->published_at) {
            $this->published_at = now();
        }

        $this->save();
    }

    /**
     * Falls back to the product's own name/description when no explicit
     * SEO override has been set for that locale.
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

    /**
     * @return array{class: string, label: string}
     */
    public function statusBadge(): array
    {
        return match ($this->status) {
            self::STATUS_PUBLISHED => ['class' => 'dj-admin-badge-success', 'label' => __('products.status_published')],
            self::STATUS_SCHEDULED => ['class' => 'dj-admin-badge-info', 'label' => __('products.status_scheduled')],
            self::STATUS_ARCHIVED => ['class' => 'dj-admin-badge-neutral', 'label' => __('products.status_archived')],
            default => ['class' => 'dj-admin-badge-gold', 'label' => __('products.status_draft')],
        };
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

    /**
     * Smaller thumbnail variant of the cover photo, for list/grid views
     * (product cards, category grids) — falls back to the full-size image
     * when no thumbnail exists yet (legacy external URLs, or images
     * uploaded before this thumbnail feature shipped).
     */
    public function getCoverThumbSrcAttribute(): ?string
    {
        $path = $this->image_url ?: $this->images->first()?->path;

        return $path ? app(ImageUploadService::class)->thumbnailUrl($path) : null;
    }

    public function getAverageRatingAttribute(): float
    {
        return round($this->approvedReviews->avg('rating') ?? 0, 1);
    }

    public function getReviewsCountAttribute(): int
    {
        return $this->approvedReviews->count();
    }

    /**
     * Count of approved reviews per star (5 down to 1), for a rating
     * distribution bar chart. Operates on the already-loaded
     * approvedReviews collection — no extra query.
     *
     * @return array<int, int>
     */
    public function getRatingDistributionAttribute(): array
    {
        $counts = $this->approvedReviews->groupBy('rating')->map->count();

        return collect(range(5, 1))->mapWithKeys(fn ($star) => [$star => $counts->get($star, 0)])->all();
    }
}
