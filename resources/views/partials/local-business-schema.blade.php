{{-- Site-wide LocalBusiness (Store) structured data, alongside Organization
     in every storefront <head>. address/telephone/openingHours are each
     omitted entirely when the backing Setting is empty — an incomplete
     schema is fine, an invalid one (empty-string values) is not. telephone
     reuses the existing whatsapp_number Setting rather than a separate
     phone field, since that's the one contact number this store already
     publishes to customers. sameAs is deliberately left empty like
     Organization's: fill in real social profile URLs here once confirmed
     rather than guessing at them. --}}
@php
    $djBusinessAddress = \App\Models\Setting::get('business_address');
    $djBusinessPhone = \App\Models\Setting::get('whatsapp_number');
    $djBusinessHours = \App\Models\Setting::get('business_hours');

    $djLocalBusinessSchema = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Store',
        'name' => \App\Models\Setting::get('store_name', config('app.name', 'Dar El Jamila')),
        'url' => url('/'),
        'image' => asset('assets/branding/favicon-512.png'),
        'address' => $djBusinessAddress ?: null,
        'telephone' => $djBusinessPhone ?: null,
        'openingHours' => $djBusinessHours ?: null,
        'sameAs' => [
            // 'https://www.facebook.com/...',
            // 'https://www.instagram.com/...',
            // 'https://www.tiktok.com/@...',
        ],
    ], fn ($value) => $value !== null);
@endphp
<script type="application/ld+json">
{!! json_encode($djLocalBusinessSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
