<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Faq extends Model
{
    /**
     * The default 5 questions the homepage/faq page show when no admin
     * has ever added a real FAQ row — the exact list that used to be
     * hardcoded directly into home.blade.php, kept here as the single
     * source of truth so both the homepage teaser and the /faq page
     * fall back to identical content instead of two hand-duplicated
     * copies drifting apart.
     */
    public static function fallbackList(): array
    {
        return [
            ['q' => __('How long does delivery take?'), 'a' => __('Delivery takes 2 to 5 business days depending on the governorate, and we send you a tracking number once your order ships.')],
            ['q' => __('How do I choose the right size?'), 'a' => __('We have a detailed size chart available, and our team is happy to help you pick the right size before confirming your order.')],
            ['q' => __('Can I exchange or return an item?'), 'a' => __('Yes, exchanges are available within 3 days of delivery, as long as the item is unused and in its original condition.')],
            ['q' => __('Can I request a custom design or size?'), 'a' => __("Absolutely — our custom tailoring service is available. Reach out and we'll help you design your piece exactly the way you want.")],
            ['q' => __('What payment methods are available?'), 'a' => __('We currently accept cash on delivery, with more payment options coming soon.')],
        ];
    }

    protected $fillable = [
        'question_ar', 'question_en', 'answer_ar', 'answer_en', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Busts the storefront home-page cache (HomeController::index(),
        // which reads active FAQs for the homepage teaser) on every
        // write. Mirrors Product/Banner/Review's identical pattern; see
        // their booted() methods for why these must NOT be arrow
        // functions (Cache::forget() returning false would silently
        // halt any later saved()/deleted() listener).
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
}
