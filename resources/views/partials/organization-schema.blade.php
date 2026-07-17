{{-- Site-wide Organization structured data — one per page, in every
     storefront <head>. sameAs is deliberately left empty: fill in real
     social profile URLs (Facebook/Instagram/TikTok/etc.) here once
     confirmed rather than guessing at them. --}}
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => \App\Models\Setting::get('store_name', config('app.name', 'Dar El Jamila')),
    'url' => url('/'),
    'logo' => asset('assets/branding/favicon-512.png'),
    'sameAs' => [
        // 'https://www.facebook.com/...',
        // 'https://www.instagram.com/...',
        // 'https://www.tiktok.com/@...',
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
