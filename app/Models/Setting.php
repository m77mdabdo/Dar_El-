<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever("setting.{$key}", function () use ($key, $default) {
            return static::where('key', $key)->value('value') ?? $default;
        });
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting.{$key}");
    }

    /**
     * Parsed sitewide-offer expiry, or null if unset — same
     * string-setting-to-Carbon-or-null parse used to live duplicated inline
     * in home.blade.php and shop/index.blade.php. Mirrors how the
     * per-product case already goes through Product::hasActiveOffer()
     * instead of each view parsing offer_ends_at itself.
     */
    public static function sitewideOfferEndsAt(): ?Carbon
    {
        $value = static::get('sitewide_offer_end_at');

        return $value ? Carbon::parse($value) : null;
    }
}
