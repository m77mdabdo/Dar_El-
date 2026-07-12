<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingMethod extends Model
{
    protected $fillable = ['name_ar', 'name_en', 'fee', 'estimated_days', 'is_active'];

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

        // A "Standard Delivery" row may already exist but be inactive (an
        // admin deactivated it without adding a replacement) — reactivate
        // it rather than creating a second, differently-named row.
        $existing = static::where('name_en', 'Standard Delivery')->first();

        if ($existing) {
            $existing->update(['is_active' => true]);

            return;
        }

        static::create([
            'name_en' => 'Standard Delivery',
            'name_ar' => 'توصيل عادي',
            'fee' => (int) Setting::get('default_shipping_fee', 0),
            'estimated_days' => '3-5',
            'is_active' => true,
        ]);
    }
}
