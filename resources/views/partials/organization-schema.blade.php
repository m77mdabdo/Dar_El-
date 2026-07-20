{{-- Site-wide Organization structured data — one per page, in every
     storefront <head>. sameAs is deliberately left empty: fill in real
     social profile URLs (Facebook/Instagram/TikTok/etc.) here once
     confirmed rather than guessing at them. --}}
@php
    // Deliberately built in this raw-PHP block rather than as an inline
    // array literal inside the echo statement below. Blade's directive
    // compiler scans raw template text for at-sign-prefixed directive
    // names regardless of surrounding PHP-string context, and the
    // Organization schema's own key name for this happens to collide with
    // Laravel's real context-sharing Blade directive when it sits directly
    // inside that kind of echo, corrupting the key with leaked
    // compiled-directive PHP instead of the plain literal string. A raw-PHP
    // block like this one is extracted and shielded from that scan first,
    // which is why every other schema partial (local-business, article,
    // breadcrumb, product) already builds its array this way — this file
    // just hadn't been, until it broke JSON-LD output on every page in
    // production.
    $djOrganizationSchema = [
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
    ];
@endphp
<script type="application/ld+json">
{!! json_encode($djOrganizationSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
