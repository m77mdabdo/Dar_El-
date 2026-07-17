@php
    $djStockStatus = $product->stockStatus();
    $djAvailability = $djStockStatus['status'] === 'out_of_stock'
        ? 'https://schema.org/OutOfStock'
        : 'https://schema.org/InStock';

    $djProductSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => trans_field($product, 'name'),
        'description' => \Illuminate\Support\Str::limit((string) trans_field($product, 'description'), 300),
        'sku' => $product->sku,
        'image' => $product->cover_image_src ? [$product->cover_image_src] : [],
        'offers' => [
            '@type' => 'Offer',
            'url' => route('shop.show', $product),
            'priceCurrency' => 'EGP',
            'price' => (string) $product->price,
            'availability' => $djAvailability,
        ],
    ];

    if ($product->reviews_count > 0) {
        $djProductSchema['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => (string) $product->average_rating,
            'reviewCount' => $product->reviews_count,
            'bestRating' => '5',
        ];
    }
@endphp
<script type="application/ld+json">
{!! json_encode($djProductSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
