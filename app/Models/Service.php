<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    /**
     * A small fixed palette of stroke-SVG icon markup an admin picks from
     * (via a plain <select>, not a visual grid — not worth more UI for a
     * handful of entries) rather than uploading an image: the services
     * page renders icons as small 58px line-art badges, not photos. Each
     * value is raw inner-SVG markup (not just a single path — a couple of
     * the original 6 icons combine a <path> with <circle>/<rect>
     * elements), rendered via {!! !!} in the view — safe since these are
     * fixed server-defined strings, never user input. The first 6 are the
     * exact original icons from the hardcoded services page, kept
     * pixel-identical so Service::fallbackList() reproduces today's page
     * exactly; heart/star are extra options for new services.
     */
    public const ICONS = [
        'pencil' => '<path d="M12 20h9M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/>',
        'ruler' => '<path d="M3 6h18M3 12h18M3 18h18"/><circle cx="7" cy="6" r="1.4" fill="currentColor" stroke="none"/><circle cx="15" cy="12" r="1.4" fill="currentColor" stroke="none"/><circle cx="10" cy="18" r="1.4" fill="currentColor" stroke="none"/>',
        'bag' => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a4 4 0 018 0v2"/>',
        'gift' => '<path d="M20 12v9H4v-9M2 7h20v5H2zM12 22V7M12 7C10 3 6 3 6 6s4 1 6 1M12 7c2-4 6-4 6-1s-4 1-6 1"/>',
        'cart' => '<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 002 1.6h9.7a2 2 0 002-1.6L23 6H6"/>',
        'card' => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>',
        'heart' => '<path d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/>',
        'star' => '<path d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z"/>',
    ];

    /**
     * The original 6 hardcoded services from resources/views/pages/services.blade.php,
     * kept here as the single source of truth for the empty-state fallback
     * — same opt-in pattern as Faq::fallbackList()/the Hero Banner
     * fallback: zero admin-created rows means the page looks exactly as
     * it always has, not blank.
     */
    public static function fallbackList(): array
    {
        return [
            ['icon' => 'pencil', 'title' => __('Custom Tailoring'), 'description' => __('We tailor your piece to your exact measurements, with your choice of color and details to match your taste.')],
            ['icon' => 'ruler', 'title' => __('Size Consultation'), 'description' => __('A detailed size chart and a team ready to help you pick the perfect fit before you confirm your order.')],
            ['icon' => 'bag', 'title' => __('Nationwide Delivery'), 'description' => __('Delivery service covering every governorate, with order tracking from confirmation to your doorstep.')],
            ['icon' => 'gift', 'title' => __('Luxury Gift Wrapping'), 'description' => __('If your order is a gift, we wrap it beautifully to match the occasion — no extra request needed.')],
            ['icon' => 'cart', 'title' => __('Events & Bulk Orders'), 'description' => __('We help coordinate matching looks for weddings, engagements, and graduation parties with special quantities and pricing.')],
            ['icon' => 'card', 'title' => __('Flexible Payment Options'), 'description' => __('Pay on delivery, with more payment options coming soon.')],
        ];
    }

    protected $fillable = [
        'title_ar', 'title_en', 'description_ar', 'description_en', 'icon', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function iconSvg(): string
    {
        return self::ICONS[$this->icon] ?? self::ICONS['star'];
    }
}
