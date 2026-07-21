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

    /**
     * One global size chart for the whole catalog (not per-category) —
     * this is a single small boutique, not a multi-brand marketplace, and
     * a per-category chart would be real maintenance overhead for fit
     * differences that the admin-editable note below already covers.
     * Falls back to reasonable real-world abaya measurements (cm) if the
     * admin hasn't customized it yet, so the size guide never ships empty.
     *
     * @return list<array{size: string, bust: int, waist: int, hips: int, length: int}>
     */
    public static function sizeGuideChart(): array
    {
        $raw = static::get('size_guide_chart');
        $decoded = $raw ? json_decode($raw, true) : null;

        return is_array($decoded) && $decoded !== [] ? $decoded : self::defaultSizeGuideChart();
    }

    public static function sizeGuideNote(): string
    {
        return static::get('size_guide_note') ?: 'قد يختلف المقاس البسيط حسب تصميم القطعة';
    }

    protected static function defaultSizeGuideChart(): array
    {
        return [
            ['size' => 'S', 'bust' => 92, 'waist' => 76, 'hips' => 100, 'length' => 140],
            ['size' => 'M', 'bust' => 96, 'waist' => 80, 'hips' => 104, 'length' => 142],
            ['size' => 'L', 'bust' => 100, 'waist' => 84, 'hips' => 108, 'length' => 144],
            ['size' => 'XL', 'bust' => 104, 'waist' => 88, 'hips' => 112, 'length' => 146],
            ['size' => 'XXL', 'bust' => 108, 'waist' => 92, 'hips' => 116, 'length' => 148],
        ];
    }
}
