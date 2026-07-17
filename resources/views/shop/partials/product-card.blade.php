@php
    $djSizes = $product->sizes->map(fn ($s) => ['size' => $s->size, 'stock' => $s->stock])->values();
    $djInStock = $product->totalStock() > 0;
    $djStockStatus = $product->stockStatus();
    $djQuickAddSize = $djSizes->firstWhere('stock', '>', 0)['size'] ?? ($djSizes->first()['size'] ?? 'M');
    $djInWishlist = in_array($product->id, $wishlistedIds ?? [], true);
    $djPayload = [
        'id' => $product->id,
        'addUrl' => route('cart.add', $product),
        'detailsUrl' => route('shop.show', $product),
        'image' => $product->cover_image_src,
        'name' => trans_field($product, 'name'),
        'description' => trans_field($product, 'description'),
        'price' => $product->price,
        'priceFormatted' => number_format($product->price).' EGP',
        'rating' => $product->reviews_count > 0 ? $product->average_rating : null,
        'ratingLabel' => __('Customer Rating'),
        'sizes' => $djSizes,
        'qtyLabel' => __('Quantity'),
        'addToCartLabel' => __('Add to Cart'),
        'outOfStockLabel' => __('Out of Stock'),
        'viewDetailsLabel' => __('View Full Details'),
        'addedMessage' => __('Added to cart ✓'),
        'errorMessage' => __('Could not add this item.'),
        'trust1' => __('Secure Order'),
        'trust2' => __('Nationwide Delivery'),
        'trust3' => __('3-Day Exchange'),
        'inWishlist' => $djInWishlist,
        'wishlistAddUrl' => auth()->check() ? route('wishlist.add', $product) : null,
        'wishlistRemoveUrl' => auth()->check() ? route('wishlist.remove', $product) : null,
        'wishlistLoginUrl' => route('login', ['redirect' => route('shop.show', $product)]),
        'wishlistAddedMessage' => __('Added to wishlist ✓'),
        'wishlistRemovedMessage' => __('Removed from wishlist'),
        'wishlistLoginMessage' => __('Please login to save wishlist'),
        'stockLabel' => $djStockStatus['label'],
        'lowStockThreshold' => \App\Models\Product::LOW_STOCK_THRESHOLD,
        'inStockLabel' => __('In Stock'),
        'lowStockLabel' => __('Only :count left'),
    ];
@endphp
<div class="dj-card dj-reveal">
    <div class="dj-swatch dj-photo-wrap dj-tint-maroon" data-product='@json($djPayload)' onclick="djOpenProductModal(JSON.parse(this.dataset.product))">
        @if ($product->cover_image_src)
            <img src="{{ $product->cover_thumb_src }}" alt="{{ trans_field($product, 'name') }}" loading="lazy">
        @endif
        <span class="dj-tag">{{ trans_field($product->category, 'name') }}</span>
        @if ($product->badge === 'bestseller')
            <span class="dj-ribbon dj-best">{{ __('Bestseller') }}</span>
        @elseif ($product->badge === 'new')
            <span class="dj-ribbon dj-new">{{ __('New') }}</span>
        @endif

        <button type="button" class="dj-wishlist-btn {{ $djInWishlist ? 'dj-active' : '' }}"
                aria-label="{{ __('Toggle Wishlist') }}" aria-pressed="{{ $djInWishlist ? 'true' : 'false' }}"
                data-wishlist-product="{{ $product->id }}"
                onclick="event.stopPropagation(); djToggleWishlist(this, {{ $product->id }})"
                data-add-url="{{ auth()->check() ? route('wishlist.add', $product) : '' }}"
                data-remove-url="{{ auth()->check() ? route('wishlist.remove', $product) : '' }}"
                data-login-url="{{ route('login', ['redirect' => route('shop.show', $product)]) }}"
                data-added-message="{{ __('Added to wishlist ✓') }}"
                data-removed-message="{{ __('Removed from wishlist') }}"
                data-login-message="{{ __('Please login to save wishlist') }}">
            <svg viewBox="0 0 24 24" fill="{{ $djInWishlist ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.8"><path d="M12 20.5s-7.5-4.6-10-9.3C.4 8 1.8 4.5 5 3.6c2-.5 4 .3 5.3 2C11.6 3.9 13.6 3 15.7 3.6c3.1.9 4.5 4.4 3 7.6-2.5 4.7-10 9.3-10 9.3Z"/></svg>
        </button>

        @if ($djStockStatus['status'] === 'out_of_stock')
            <div class="dj-soldout-overlay">{{ __('Out of Stock') }}</div>
        @endif
    </div>
    <div class="dj-card-body">
        <a href="{{ route('shop.show', $product) }}"><h3>{{ trans_field($product, 'name') }}</h3></a>
        @if ($product->reviews_count > 0)
            <div class="dj-rating">★★★★★ <span class="dj-rn">{{ $product->average_rating }}</span></div>
        @endif
        <div class="dj-desc">{{ \Illuminate\Support\Str::limit(trans_field($product, 'description'), 50) }}</div>
        <div class="dj-stock-badge dj-{{ str_replace('_', '-', $djStockStatus['status']) }}">{{ $djStockStatus['label'] }}</div>
        <div class="dj-card-bottom">
            <span class="dj-price">{{ number_format($product->price) }} EGP</span>
            <button type="button" class="dj-add-btn" {{ $djInStock ? '' : 'disabled' }}
                onclick='event.stopPropagation(); djAddToCart(@json(route('cart.add', $product)), @json($djQuickAddSize), 1, @json(__('Added to cart ✓')), @json(__('Could not add this item.')), { id: {{ $product->id }}, name: @json(trans_field($product, 'name')), price: {{ $product->price }} })'>
                {{ $djInStock ? __('Add to Cart') : __('Out of Stock') }}
            </button>
        </div>
    </div>
</div>
