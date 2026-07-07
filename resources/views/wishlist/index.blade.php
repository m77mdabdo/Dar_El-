@extends('layouts.storefront')

@section('title', __('My Wishlist') . ' — Dar El-Jamila')

@section('content')
    <div class="max-w-5xl mx-auto px-4 sm:px-6 py-12">
        <h1 style="font-size:28px; color:var(--dj-maroon); margin-bottom:30px;">{{ __('My Wishlist') }}</h1>

        @if ($wishlists->isEmpty())
            <div class="dj-empty-cart" style="text-align:center;">
                ♡<br>{{ __('Your wishlist is empty.') }}
                <br><a href="{{ route('shop.index') }}" style="color:var(--dj-maroon); text-decoration:underline;">{{ __('Continue shopping') }}</a>
            </div>
        @else
            <div class="dj-wishlist-grid">
                @foreach ($wishlists as $wishlist)
                    @php
                        $product = $wishlist->product;
                        $sizes = $product->sizes->map(fn ($s) => ['size' => $s->size, 'stock' => $s->stock])->values();
                        $defaultSize = $sizes->firstWhere('stock', '>', 0)['size'] ?? null;
                        $stockStatus = $product->stockStatus();
                    @endphp
                    <div class="dj-card dj-wishlist-card" id="dj-wishlist-item-{{ $product->id }}">
                        <a href="{{ route('shop.show', $product) }}" class="dj-swatch dj-photo-wrap dj-tint-maroon">
                            @if ($product->cover_image_src)
                                <img src="{{ $product->cover_image_src }}" alt="{{ trans_field($product, 'name') }}" loading="lazy">
                            @endif
                            @if ($stockStatus['status'] === 'out_of_stock')
                                <div class="dj-soldout-overlay">{{ __('Out of Stock') }}</div>
                            @endif
                        </a>
                        <div class="dj-card-body">
                            <a href="{{ route('shop.show', $product) }}"><h3>{{ trans_field($product, 'name') }}</h3></a>
                            <div class="dj-stock-badge dj-{{ str_replace('_', '-', $stockStatus['status']) }}">{{ $stockStatus['label'] }}</div>
                            <div class="dj-card-bottom" style="margin-bottom:12px;">
                                <span class="dj-price">{{ number_format($product->price) }} EGP</span>
                            </div>

                            @if ($sizes->isNotEmpty())
                                <div class="dj-sizes dj-wishlist-sizes">
                                    @foreach ($sizes as $s)
                                        <div class="dj-size-opt {{ $s['size'] === $defaultSize ? 'dj-active' : '' }} {{ $s['stock'] <= 0 ? 'dj-disabled' : '' }}"
                                             data-size="{{ $s['size'] }}" onclick="{{ $s['stock'] > 0 ? 'djWishlistSelectSize(this)' : '' }}">{{ $s['size'] }}</div>
                                    @endforeach
                                </div>
                            @endif

                            <div class="dj-wishlist-actions">
                                <button type="button" class="dj-add-btn" {{ $defaultSize ? '' : 'disabled' }}
                                    onclick="djWishlistMoveToCart(this, {{ $product->id }})"
                                    data-move-url="{{ route('wishlist.move', $product) }}"
                                    data-added-message="{{ __('Moved to cart') }}" data-error-message="{{ __('Could not move this item.') }}">
                                    {{ $defaultSize ? __('Move to Cart') : __('Out of Stock') }}
                                </button>
                                <button type="button" class="dj-ci-remove dj-wishlist-remove"
                                    onclick="djWishlistRemove(this, {{ $product->id }})"
                                    data-remove-url="{{ route('wishlist.remove', $product) }}"
                                    data-removed-message="{{ __('Removed from wishlist') }}">
                                    {{ __('Remove') }}
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <script>
        function djWishlistSelectSize(el) {
            el.parentElement.querySelectorAll('.dj-size-opt').forEach(o => o.classList.remove('dj-active'));
            el.classList.add('dj-active');
        }

        async function djWishlistMoveToCart(btn, productId) {
            const card = document.getElementById('dj-wishlist-item-' + productId);
            const selected = card.querySelector('.dj-size-opt.dj-active');
            if (!selected) {
                djShowToast('{{ __('Please choose a size.') }}');
                return;
            }
            btn.disabled = true;
            try {
                const data = await djFetch(btn.dataset.moveUrl, 'POST', { size: selected.dataset.size });
                djShowToast(btn.dataset.addedMessage);
                if (typeof data.cart_count === 'number') {
                    const countEl = document.getElementById('dj-cart-count');
                    if (countEl) countEl.textContent = data.cart_count;
                }
                if (typeof data.wishlist_count === 'number') {
                    const wEl = document.getElementById('dj-wishlist-count');
                    if (wEl) wEl.textContent = data.wishlist_count;
                }
                card.remove();
            } catch (e) {
                djShowToast(e.data?.error || btn.dataset.errorMessage);
                btn.disabled = false;
            }
        }

        async function djWishlistRemove(btn, productId) {
            const card = document.getElementById('dj-wishlist-item-' + productId);
            btn.disabled = true;
            try {
                const data = await djFetch(btn.dataset.removeUrl, 'DELETE');
                djShowToast(btn.dataset.removedMessage);
                if (typeof data.count === 'number') {
                    const wEl = document.getElementById('dj-wishlist-count');
                    if (wEl) wEl.textContent = data.count;
                }
                card.remove();
            } catch (e) {
                djShowToast('{{ __('Could not update wishlist.') }}');
                btn.disabled = false;
            }
        }
    </script>
@endsection
