@extends('layouts.storefront')

@section('title', trans_field($product, 'name') . ' — Dar El-Jamila')
@section('meta_description', \Illuminate\Support\Str::limit(trans_field($product, 'description'), 150))
@section('og_image', $product->cover_image_src ?? asset('favicon.ico'))

@section('content')
    <div class="max-w-5xl mx-auto px-4 sm:px-6 py-12">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
            <div class="dj-photo-wrap dj-tint-maroon" style="aspect-ratio:1; border-radius:20px; overflow:hidden;">
                @if ($product->cover_image_src)
                    <img src="{{ $product->cover_image_src }}" alt="{{ trans_field($product, 'name') }}">
                @else
                    <div style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center; background:var(--dj-cream-2); color:#8a6b70;">{{ __('No image') }}</div>
                @endif
            </div>

            <div>
                <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px;">
                    <p style="font-size:12px; letter-spacing:2px; text-transform:uppercase; color:var(--dj-rose-dust); margin-bottom:8px;">{{ trans_field($product->category, 'name') }}</p>
                    @auth
                        <button type="button" class="dj-wishlist-btn dj-wishlist-btn-static {{ in_array($product->id, $wishlistedIds ?? [], true) ? 'dj-active' : '' }}"
                                aria-label="{{ __('Toggle Wishlist') }}" data-wishlist-product="{{ $product->id }}"
                                onclick="djToggleWishlist(this, {{ $product->id }})"
                                data-add-url="{{ route('wishlist.add', $product) }}" data-remove-url="{{ route('wishlist.remove', $product) }}"
                                data-login-url="{{ route('login', ['redirect' => route('shop.show', $product)]) }}"
                                data-added-message="{{ __('Added to wishlist ✓') }}" data-removed-message="{{ __('Removed from wishlist') }}"
                                data-login-message="{{ __('Please login to save wishlist') }}">
                            <svg viewBox="0 0 24 24" fill="{{ in_array($product->id, $wishlistedIds ?? [], true) ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.8"><path d="M12 20.5s-7.5-4.6-10-9.3C.4 8 1.8 4.5 5 3.6c2-.5 4 .3 5.3 2C11.6 3.9 13.6 3 15.7 3.6c3.1.9 4.5 4.4 3 7.6-2.5 4.7-10 9.3-10 9.3Z"/></svg>
                        </button>
                    @else
                        <a href="{{ route('login', ['redirect' => route('shop.show', $product)]) }}" class="dj-wishlist-btn dj-wishlist-btn-static" aria-label="{{ __('Toggle Wishlist') }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 20.5s-7.5-4.6-10-9.3C.4 8 1.8 4.5 5 3.6c2-.5 4 .3 5.3 2C11.6 3.9 13.6 3 15.7 3.6c3.1.9 4.5 4.4 3 7.6-2.5 4.7-10 9.3-10 9.3Z"/></svg>
                        </a>
                    @endauth
                </div>
                <h1 style="font-size:28px; color:var(--dj-maroon); margin-bottom:10px;">{{ trans_field($product, 'name') }}</h1>
                @if ($product->reviews_count > 0)
                    <div class="dj-rating" style="margin-bottom:10px;">★★★★★ <span class="dj-rn">{{ $product->average_rating }} · {{ __('Customer Rating') }}</span></div>
                @endif
                <p class="dj-price" style="font-size:22px; margin-bottom:18px;">{{ number_format($product->price) }} EGP</p>
                <p style="font-size:14px; color:#8a6b70; line-height:1.9; margin-bottom:24px;">{{ trans_field($product, 'description') }}</p>

                <form id="dj-pdp-form" data-add-url="{{ route('cart.add', $product) }}"
                      data-sizes='@json($product->sizes->map(fn ($s) => ["size" => $s->size, "stock" => $s->stock]))'
                      data-low-stock-threshold="{{ \App\Models\Product::LOW_STOCK_THRESHOLD }}"
                      data-in-stock-label="{{ __('In Stock') }}" data-low-stock-label="{{ __('Only :count left') }}" data-out-of-stock-label="{{ __('Out of Stock') }}">
                    @csrf
                    <div class="dj-sizes" style="margin-bottom:12px;">
                        @foreach ($product->sizes as $size)
                            <div class="dj-size-opt {{ $loop->first && $size->stock > 0 ? 'dj-active' : '' }} {{ $size->stock <= 0 ? 'dj-disabled' : '' }}"
                                 data-size="{{ $size->size }}" onclick="{{ $size->stock > 0 ? 'djPdpSelectSize(this)' : '' }}">{{ $size->size }}</div>
                        @endforeach
                    </div>

                    <div id="dj-pdp-stock" class="dj-stock-badge" style="margin-bottom:16px;"></div>

                    <div class="dj-qty-select">
                        <span style="font-size:12.5px; color:#8a6b70;">{{ __('Quantity') }}</span>
                        <button type="button" id="dj-pdp-qty-minus" onclick="djPdpChangeQty(-1)">-</button>
                        <span id="dj-pdp-qty">1</span>
                        <button type="button" id="dj-pdp-qty-plus" onclick="djPdpChangeQty(1)">+</button>
                    </div>

                    <button type="button" onclick="djPdpAddToCart()" id="dj-pdp-add-btn" class="dj-modal-add">
                        {{ __('Add to Cart') }}
                    </button>

                    <div class="dj-modal-trust">
                        <span>{{ __('Secure Order') }}</span>
                        <span>{{ __('Nationwide Delivery') }}</span>
                        <span>{{ __('3-Day Exchange') }}</span>
                    </div>
                </form>
            </div>
        </div>

        <div style="margin-top:70px;">
            <div class="dj-section-title" style="text-align:left; padding:0 0 20px;">
                <h2 style="font-size:24px;">{{ __('Reviews') }}</h2>
            </div>
            @forelse ($product->approvedReviews as $review)
                <div style="border-bottom:1px solid var(--dj-cream-2); padding:16px 0;">
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
                        <span style="font-weight:700; font-size:14px; color:var(--dj-maroon);">{{ $review->name }}</span>
                        <span class="dj-stars" style="margin-bottom:0;">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</span>
                    </div>
                    <p style="font-size:13.5px; color:#8a6b70;">{{ $review->comment }}</p>
                </div>
            @empty
                <p style="font-size:14px; color:#8a6b70;">{{ __('No reviews yet.') }}</p>
            @endforelse
        </div>

        @if ($relatedProducts->isNotEmpty())
            <div style="margin-top:20px;">
                <div class="dj-section-title" style="text-align:left; padding:20px 0;">
                    <h2 style="font-size:24px;">{{ __('You May Also Like') }}</h2>
                </div>
            </div>
        @endif
    </div>

    @if ($relatedProducts->isNotEmpty())
        <div class="dj-grid" style="padding-top:0;">
            @foreach ($relatedProducts as $related)
                @include('shop.partials.product-card', ['product' => $related])
            @endforeach
        </div>
    @endif

    <script>
        let djPdpQty = 1;
        const djPdpForm = document.getElementById('dj-pdp-form');
        const djPdpSizes = JSON.parse(djPdpForm.dataset.sizes || '[]');
        const djPdpLowStockThreshold = parseInt(djPdpForm.dataset.lowStockThreshold, 10) || 5;

        function djPdpStockFor(size) {
            return djPdpSizes.find(s => s.size === size)?.stock ?? 0;
        }

        function djPdpRefreshStockUi() {
            const selected = djPdpForm.querySelector('.dj-size-opt.dj-active');
            const stock = selected ? djPdpStockFor(selected.dataset.size) : 0;
            const badge = document.getElementById('dj-pdp-stock');
            const addBtn = document.getElementById('dj-pdp-add-btn');

            let label, cls;
            if (stock <= 0) {
                label = djPdpForm.dataset.outOfStockLabel;
                cls = 'dj-out-of-stock';
            } else if (stock <= djPdpLowStockThreshold) {
                label = djPdpForm.dataset.lowStockLabel.replace(':count', stock);
                cls = 'dj-low-stock';
            } else {
                label = djPdpForm.dataset.inStockLabel;
                cls = 'dj-in-stock';
            }
            badge.textContent = label;
            badge.className = 'dj-stock-badge ' + cls;

            djPdpQty = Math.max(1, Math.min(djPdpQty, stock || 1));
            document.getElementById('dj-pdp-qty').textContent = djPdpQty;
            document.getElementById('dj-pdp-qty-minus').disabled = djPdpQty <= 1;
            document.getElementById('dj-pdp-qty-plus').disabled = djPdpQty >= stock;

            addBtn.disabled = stock <= 0;
            addBtn.textContent = stock <= 0 ? djPdpForm.dataset.outOfStockLabel : '{{ __('Add to Cart') }}';
        }

        function djPdpSelectSize(el) {
            djPdpForm.querySelectorAll('.dj-size-opt').forEach(o => o.classList.remove('dj-active'));
            el.classList.add('dj-active');
            djPdpQty = 1;
            djPdpRefreshStockUi();
        }
        function djPdpChangeQty(delta) {
            const selected = djPdpForm.querySelector('.dj-size-opt.dj-active');
            const stock = selected ? djPdpStockFor(selected.dataset.size) : 1;
            djPdpQty = Math.max(1, Math.min(stock, djPdpQty + delta));
            djPdpRefreshStockUi();
        }
        async function djPdpAddToCart() {
            const selected = djPdpForm.querySelector('.dj-size-opt.dj-active');
            if (!selected) {
                djShowToast('{{ __('Please choose a size.') }}');
                return;
            }
            await djAddToCart(djPdpForm.dataset.addUrl, selected.dataset.size, djPdpQty, '{{ __('Added to cart ✓') }}', '{{ __('Could not add this item.') }}');
        }

        djPdpRefreshStockUi();
    </script>
@endsection
