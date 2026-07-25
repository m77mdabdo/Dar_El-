<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Review extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id', 'user_id', 'name', 'rating', 'title', 'comment',
        'status', 'is_verified_purchase', 'is_featured', 'helpful_count',
        'approved_at', 'approved_by', 'rejected_at', 'rejected_by', 'rejection_reason',
    ];

    protected static function booted(): void
    {
        // Busts the storefront home-page cache (HomeController::index(),
        // which reads approved+featured reviews for the testimonials
        // section) on every write — approve/reject, feature/unfeature,
        // delete, all fire saved()/deleted(). Mirrors Product/Banner's
        // identical cache-busting; see their booted() for why these must
        // NOT be arrow functions (Cache::forget() returning false would
        // silently halt any later listener).
        static::saved(function () {
            Cache::forget('storefront.home.data');
        });
        static::deleted(function () {
            Cache::forget('storefront.home.data');
        });
    }

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_verified_purchase' => 'boolean',
            'is_featured' => 'boolean',
            'helpful_count' => 'integer',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ReviewImage::class)->orderBy('sort_order');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
}
