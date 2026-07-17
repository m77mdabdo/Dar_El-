{{-- Reusable BreadcrumbList structured data. $breadcrumbs is an ordered list
     of ['name' => ..., 'url' => ...] pairs — null entries are dropped first
     so callers can conditionally include a level (e.g. a product's category)
     with a plain ternary/array_filter rather than @if-branching the include
     itself. --}}
@php
    $djBreadcrumbSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => collect($breadcrumbs)->filter()->values()->map(fn ($crumb, $index) => [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'name' => $crumb['name'],
            'item' => $crumb['url'],
        ])->all(),
    ];
@endphp
<script type="application/ld+json">
{!! json_encode($djBreadcrumbSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
