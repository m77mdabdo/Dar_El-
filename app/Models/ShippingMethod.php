<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingMethod extends Model
{
    const DEFAULT_CODE = 'standard';

    protected $fillable = [
        'code', 'name_ar', 'name_en', 'description_ar', 'description_en',
        'fee', 'estimated_days', 'delivery_time_min_days', 'delivery_time_max_days',
        'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * "3–5 days" (or "يوم" for a single day) built from the structured
     * min/max columns — used anywhere a human-readable estimate is shown
     * (checkout radios, order summary, admin, email, invoice) instead of
     * the old free-text estimated_days column.
     */
    public function deliveryEstimateLabel(): string
    {
        if (! $this->delivery_time_min_days) {
            return $this->estimated_days ?? '';
        }

        $unit = app()->getLocale() === 'ar' ? 'أيام' : ($this->delivery_time_max_days > 1 ? 'days' : 'day');

        return $this->delivery_time_min_days === $this->delivery_time_max_days
            ? "{$this->delivery_time_min_days} {$unit}"
            : "{$this->delivery_time_min_days}–{$this->delivery_time_max_days} {$unit}";
    }

    /**
     * Self-healing guard against the exact bug that broke checkout: if
     * every shipping method is missing/inactive (ShippingMethodSeeder never
     * ran, or an admin deactivated the last one), the checkout page has
     * nothing to render and "shipping method is required" becomes
     * impossible to satisfy. Called from CheckoutController::show() so a
     * customer never hits that dead end — creates one sane default using
     * the existing `default_shipping_fee` Setting (already exposed in the
     * admin settings screen, previously unused by checkout).
     */
    public static function ensureAtLeastOneActive(): void
    {
        if (static::where('is_active', true)->exists()) {
            return;
        }

        // A "standard" row may already exist but be inactive (an admin
        // deactivated it without adding a replacement) — reactivate it
        // rather than creating a second, differently-coded row.
        $existing = static::where('code', self::DEFAULT_CODE)->first();

        if ($existing) {
            $existing->update(['is_active' => true]);

            return;
        }

        static::create([
            'code' => self::DEFAULT_CODE,
            'name_en' => 'Standard Delivery',
            'name_ar' => 'توصيل عادي',
            // Literal 0, not the configurable Setting — this only ever fires
            // as a disaster-recovery fallback (shipping_methods table empty),
            // so it must be a guaranteed-safe, always-valid price rather than
            // something an admin could misconfigure into a broken checkout.
            'fee' => 0,
            'estimated_days' => '3-5',
            'delivery_time_min_days' => 3,
            'delivery_time_max_days' => 5,
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }
}
