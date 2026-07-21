@extends('layouts.storefront')

@section('title', $product->seoTitle(app()->getLocale()) . ' — Dar El Jamila')
@section('meta_description', \Illuminate\Support\Str::limit($product->seoDescription(app()->getLocale()), 150))
@section('og_image', $product->cover_image_src ?? asset('assets/branding/favicon-512.png'))
@section('og_type', 'product')
@section('canonical', route('shop.show', $product))
@section('structured_data')
    @include('partials.product-schema', ['product' => $product])
    @include('partials.breadcrumb-schema', ['breadcrumbs' => [
        ['name' => __('Home'), 'url' => route('home')],
        ['name' => __('Shop'), 'url' => route('shop.index')],
        $product->category ? ['name' => trans_field($product->category, 'name'), 'url' => route('shop.index', ['category' => $product->category->slug])] : null,
        ['name' => trans_field($product, 'name'), 'url' => route('shop.show', $product)],
    ]])
@endsection

@section('content')
    <div class="max-w-5xl mx-auto px-4 sm:px-6 py-12">
        <nav aria-label="{{ __('Breadcrumb') }}" class="dj-breadcrumb">
            <ol>
                <li><a href="{{ route('home') }}">{{ __('Home') }}</a></li>
                <li><a href="{{ route('shop.index') }}">{{ __('Shop') }}</a></li>
                @if ($product->category)
                    <li><a href="{{ route('shop.index', ['category' => $product->category->slug]) }}">{{ trans_field($product->category, 'name') }}</a></li>
                @endif
                <li aria-current="page">{{ trans_field($product, 'name') }}</li>
            </ol>
        </nav>

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
                    <p style="font-size:12px; letter-spacing:2px; text-transform:uppercase; color:var(--dj-rose-dust); margin-bottom:8px;">
                        {{ trans_field($product->category, 'name') }}
                        @if ($product->brand)
                            <span style="opacity:.6;">·</span> {{ trans_field($product->brand, 'name') }}
                        @endif
                    </p>
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

                @php
                    // A product's own offer_ends_at takes precedence over the
                    // site-wide countdown on its own page — showing both at
                    // once would be confusing, so this resolves to exactly
                    // one banner (or none).
                    if ($product->hasActiveOffer()) {
                        $djOfferEndsAt = $product->offer_ends_at;
                        $djOfferLabel = __('Limited-Time Offer on This Item');
                    } else {
                        $djOfferEndsAt = \App\Models\Setting::get('sitewide_offer_end_at');
                        $djOfferEndsAt = $djOfferEndsAt ? \Illuminate\Support\Carbon::parse($djOfferEndsAt) : null;
                        $djOfferLabel = \App\Models\Setting::get('sitewide_offer_label');
                    }
                @endphp
                @include('partials.offer-countdown', ['endsAt' => $djOfferEndsAt, 'label' => $djOfferLabel])

                <p style="font-size:14px; color:#8a6b70; line-height:1.9; margin-bottom:24px;">{{ trans_field($product, 'description') }}</p>

                @php
                    // A product with 0 or 1 size rows has no meaningful size
                    // choice for the customer to make — its "notify me" is
                    // whole-product-level, replacing Add to Cart entirely,
                    // rather than a per-size trigger in a selector that would
                    // only ever show one (already-disabled) option.
                    $hasMultipleSizes = $product->sizes->count() > 1;
                    $wholeProductOutOfStock = ! $hasMultipleSizes && $product->totalStock() <= 0;
                @endphp

                @if (! $wholeProductOutOfStock)
                    <form id="dj-pdp-form" data-add-url="{{ route('cart.add', $product) }}"
                          data-sizes='@json($product->sizes->map(fn ($s) => ["size" => $s->size, "stock" => $s->stock]))'
                          data-low-stock-threshold="{{ \App\Models\Product::LOW_STOCK_THRESHOLD }}"
                          data-in-stock-label="{{ __('In Stock') }}" data-low-stock-label="{{ __('Only :count left') }}" data-out-of-stock-label="{{ __('Out of Stock') }}">
                        @csrf
                        <div class="dj-sizes" style="margin-bottom:12px;">
                            @foreach ($product->sizes as $size)
                                <div class="dj-size-wrap">
                                    <div class="dj-size-opt {{ $loop->first && $size->stock > 0 ? 'dj-active' : '' }} {{ $size->stock <= 0 ? 'dj-disabled' : '' }}"
                                         data-size="{{ $size->size }}" onclick="{{ $size->stock > 0 ? 'djPdpSelectSize(this)' : '' }}">{{ $size->size }}</div>
                                    @if ($hasMultipleSizes && $size->stock <= 0)
                                        <button type="button" class="dj-size-bell" onclick='djOpenNotifyMe({{ $size->id }}, @json($size->size))' aria-label="{{ __('Notify me') }}" title="{{ __('Notify me') }}">🔔</button>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <button type="button" class="dj-size-guide-trigger dj-keep-clickable" onclick="djOpenSizeGuide()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8.25h18M3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V8.25M3 8.25l1.5-4.5h15l1.5 4.5M8.25 12v3M12 12v6M15.75 12v3"/></svg>
                            {{ __('Size Guide') }}
                        </button>

                        <div id="dj-pdp-stock" class="dj-stock-badge" style="margin-bottom:16px;"></div>

                        <div class="dj-qty-select">
                            <span style="font-size:12.5px; color:#8a6b70;">{{ __('Quantity') }}</span>
                            <button type="button" id="dj-pdp-qty-minus" onclick="djPdpChangeQty(-1)">-</button>
                            <span id="dj-pdp-qty">1</span>
                            <button type="button" id="dj-pdp-qty-plus" onclick="djPdpChangeQty(1)">+</button>
                        </div>

                        <button type="button" onclick="djPdpAddToCart()" id="dj-pdp-add-btn" class="dj-modal-add dj-keep-clickable">
                            {{ __('Add to Cart') }}
                        </button>
                    </form>
                @else
                    <button type="button" class="dj-modal-add" onclick="djOpenNotifyMe(null, null)">
                        🔔 {{ __('Notify me when back in stock') }}
                    </button>
                @endif

                @if ($whatsapp = \App\Models\Setting::get('whatsapp_number'))
                    {{-- Inline (not resources/css/app.css) so correct sizing ships the moment
                         this Blade file reaches production via a plain git pull — it doesn't
                         depend on npm run build + redeploying the compiled asset bundle. --}}
                    <style>
                        .dj-ask-whatsapp {
                            margin-top: 12px; display: flex; align-items: center; justify-content: center; gap: 8px;
                            background: transparent; border: 1.5px solid var(--dj-maroon); color: var(--dj-maroon);
                            font-weight: 700; font-size: 14px; padding: 14px; border-radius: 12px; width: 100%;
                            transition: background .2s, color .2s;
                        }
                        .dj-ask-whatsapp:hover { background: var(--dj-maroon); color: var(--dj-gold); }
                        .dj-ask-whatsapp svg { width: 18px; height: 18px; flex-shrink: 0; }
                    </style>
                    <a class="dj-ask-whatsapp"
                       href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $whatsapp) }}?text={{ rawurlencode('مهتمة بالمنتج: '.trans_field($product, 'name').' - '.route('shop.show', $product)) }}"
                       target="_blank" rel="noopener">
                        <svg viewBox="0 0 32 32" fill="currentColor" aria-hidden="true">
                            <path d="M16.001 3C9.096 3 3.5 8.596 3.5 15.5c0 2.348.646 4.54 1.767 6.417L3 29l7.27-2.217A12.42 12.42 0 0 0 16 28.5c6.905 0 12.5-5.596 12.5-12.5S22.906 3 16.001 3Zm7.32 17.688c-.312.878-1.552 1.61-2.532 1.816-.673.14-1.552.253-4.51-.968-3.786-1.564-6.223-5.402-6.412-5.652-.182-.25-1.532-2.038-1.532-3.888 0-1.85.973-2.756 1.32-3.135.312-.34.68-.425.907-.425.227 0 .454.002.652.013.21.011.492-.08.769.586.312.75 1.061 2.6 1.153 2.79.091.19.152.412.03.663-.12.25-.182.406-.363.625-.182.219-.383.489-.546.657-.182.19-.372.396-.16.774.212.378.941 1.552 2.02 2.514 1.388 1.24 2.56 1.623 2.938 1.805.379.181.6.152.82-.091.222-.242.95-1.106 1.204-1.485.253-.379.505-.31.85-.19.348.121 2.196 1.036 2.573 1.224.379.19.63.284.72.442.091.16.091.923-.222 1.8Z"/>
                        </svg>
                        {{ __('Ask about this product') }}
                    </a>
                @endif

                <div class="dj-modal-trust">
                    <span>{{ __('Secure Order') }}</span>
                    <span>{{ __('Nationwide Delivery') }}</span>
                    <span>{{ __('3-Day Exchange') }}</span>
                </div>
            </div>
        </div>

        {{-- Inline (not resources/css/app.css or resources/js/app.js) so the
             size guide ships the moment this Blade file reaches production
             via a plain git pull — same standing deploy-proofing convention
             as the WhatsApp button and order-history card above/elsewhere. --}}
        <div id="dj-size-guide-overlay" class="dj-size-guide-overlay" onclick="if (event.target === this) djCloseSizeGuide()">
            <div class="dj-size-guide-modal" role="dialog" aria-modal="true" aria-label="{{ __('Size Guide') }}">
                <button type="button" class="dj-size-guide-close" onclick="djCloseSizeGuide()" aria-label="{{ __('Close') }}">&times;</button>
                <h2>{{ __('Size Guide') }}</h2>
                <div class="dj-size-guide-table-wrap">
                    <table class="dj-size-guide-table">
                        <thead>
                            <tr>
                                <th>{{ __('settings.size_guide_size') }}</th>
                                <th>{{ __('settings.size_guide_bust') }}</th>
                                <th>{{ __('settings.size_guide_waist') }}</th>
                                <th>{{ __('settings.size_guide_hips') }}</th>
                                <th>{{ __('settings.size_guide_length') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (\App\Models\Setting::sizeGuideChart() as $row)
                                <tr>
                                    <td class="dj-size-guide-size-cell">{{ $row['size'] }}</td>
                                    <td>{{ $row['bust'] }}</td>
                                    <td>{{ $row['waist'] }}</td>
                                    <td>{{ $row['hips'] }}</td>
                                    <td>{{ $row['length'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="dj-size-guide-note">{{ \App\Models\Setting::sizeGuideNote() }}</p>
            </div>
        </div>

        {{-- Floating shortcut to the same size guide, product pages only —
             the chart data above is specific to this product, so this button
             has nothing useful to do (and nowhere sensible to send someone)
             on any page that isn't a product page. Stacks directly above the
             site-wide WhatsApp float (see partials/whatsapp-float.blade.php).
             #dj-back-to-top is pushed up below (scoped to this page only, not
             app.css — every other page keeps its normal WhatsApp-only gap) so
             none of the three ever overlap. Same inline-CSS convention as the
             size guide overlay above, for the same deploy-proofing reason. --}}
        <button type="button" id="dj-size-guide-float" class="dj-keep-clickable" onclick="djOpenSizeGuide()" aria-label="{{ __('Size Guide') }}" title="{{ __('Size Guide') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8.25h18M3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V8.25M3 8.25l1.5-4.5h15l1.5 4.5M8.25 12v3M12 12v6M15.75 12v3"/></svg>
        </button>

        <style>
            #dj-size-guide-float {
                position: fixed; bottom: 92px; right: 26px; width: 56px; height: 56px; border-radius: 50%;
                background: var(--dj-maroon); color: var(--dj-gold); display: flex; align-items: center; justify-content: center;
                box-shadow: var(--dj-shadow); z-index: 85; transition: background .2s, transform .15s;
            }
            #dj-size-guide-float:hover { background: var(--dj-maroon-dark); transform: scale(1.06); }
            #dj-size-guide-float svg { width: 26px; height: 26px; }
            body.dj-en #dj-size-guide-float { right: auto; left: 26px; }

            /* This page only: #dj-back-to-top's site-wide position (app.css)
               leaves just enough room for the WhatsApp float below it — here
               there's a third circle in the stack, so it needs to sit higher:
               92px (this button's own position) + 56px (its height) + 10px
               (the same gap the site already uses between WhatsApp and
               back-to-top) = 158px. */
            #dj-back-to-top { bottom: 158px; }

            .dj-size-guide-trigger {
                display: inline-flex; align-items: center; gap: 7px; background: transparent;
                border: 1.5px solid var(--dj-cream-2); color: var(--dj-maroon); font-size: 12.5px; font-weight: 700;
                padding: 9px 16px; border-radius: 10px; margin-bottom: 16px; transition: background .2s, border-color .2s, transform .1s;
            }
            .dj-size-guide-trigger:hover { background: var(--dj-cream-2); border-color: var(--dj-maroon); }
            .dj-size-guide-trigger:active { transform: scale(.97); }
            .dj-size-guide-trigger svg { width: 16px; height: 16px; flex-shrink: 0; }

            .dj-size-guide-overlay {
                position: fixed; inset: 0; z-index: 300; background: rgba(60,11,23,.55);
                display: flex; align-items: center; justify-content: center; padding: 20px;
                opacity: 0; visibility: hidden; pointer-events: none; transition: opacity .25s ease, visibility .25s ease;
            }
            .dj-size-guide-overlay.dj-show { opacity: 1; visibility: visible; pointer-events: auto; }
            .dj-size-guide-modal {
                background: #fff; border-radius: 18px; box-shadow: var(--dj-shadow); padding: 28px 24px;
                max-width: 480px; width: 100%; max-height: 86vh; overflow-y: auto; position: relative;
                transform: scale(.96) translateY(8px); transition: transform .25s ease;
            }
            .dj-size-guide-overlay.dj-show .dj-size-guide-modal { transform: scale(1) translateY(0); }
            .dj-size-guide-modal h2 {
                font-family: 'Tajawal'; font-size: 20px; color: var(--dj-maroon); margin-bottom: 16px; padding-inline-end: 30px;
            }
            body.dj-en .dj-size-guide-modal h2 { font-family: 'Playfair Display'; }
            .dj-size-guide-close {
                position: absolute; top: 16px; inset-inline-end: 16px; width: 32px; height: 32px; border-radius: 50%;
                background: var(--dj-cream-2); color: var(--dj-maroon); font-size: 18px; line-height: 1;
                display: flex; align-items: center; justify-content: center; transition: background .2s;
            }
            .dj-size-guide-close:hover { background: var(--dj-gold); }
            .dj-size-guide-table-wrap { overflow-x: auto; }
            .dj-size-guide-table { width: 100%; border-collapse: collapse; font-size: 13px; white-space: nowrap; }
            .dj-size-guide-table th, .dj-size-guide-table td {
                padding: 10px 8px; text-align: center; border-bottom: 1px solid var(--dj-cream-2);
                font-variant-numeric: tabular-nums;
            }
            .dj-size-guide-table th { color: var(--dj-rose-dust); font-weight: 700; font-size: 11.5px; text-transform: uppercase; letter-spacing: .3px; }
            .dj-size-guide-size-cell { font-weight: 700; color: var(--dj-maroon); }
            .dj-size-guide-note { font-size: 12px; color: #8a6b70; line-height: 1.7; margin-top: 16px; }
            @media (prefers-reduced-motion: reduce) {
                .dj-size-guide-overlay, .dj-size-guide-modal { transition: opacity .15s ease; transform: none !important; }
            }
        </style>

        <script>
            window.djOpenSizeGuide = function () {
                document.getElementById('dj-size-guide-overlay')?.classList.add('dj-show');
            };
            window.djCloseSizeGuide = function () {
                document.getElementById('dj-size-guide-overlay')?.classList.remove('dj-show');
            };
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') djCloseSizeGuide();
            });
        </script>

        @include('partials.back-in-stock-notify', ['product' => $product])

        <div style="margin-top:70px; max-width:640px;">
            <div class="dj-section-title" style="text-align:left; padding:0 0 20px;">
                <h2 style="font-size:24px;">{{ __('reviews.rating_summary') }}</h2>
            </div>

            @if ($product->reviews_count > 0)
                <div style="display:flex; align-items:center; gap:24px; margin-bottom:24px; flex-wrap:wrap;">
                    <div style="text-align:center;">
                        <p style="font-size:40px; font-weight:700; color:var(--dj-maroon); line-height:1;">{{ $product->average_rating }}</p>
                        <p class="dj-stars" style="margin-bottom:2px;">{{ str_repeat('★', round($product->average_rating)) }}{{ str_repeat('☆', 5 - round($product->average_rating)) }}</p>
                        <p style="font-size:12px; color:#8a6b70;">{{ $product->reviews_count }} {{ __('reviews.title') }}</p>
                    </div>
                    <div style="flex:1; min-width:200px;">
                        @foreach ($product->rating_distribution as $star => $count)
                            <div class="dj-rating-bar-row">
                                <span style="width:28px;">{{ $star }}★</span>
                                <span class="dj-rating-bar-track"><span class="dj-rating-bar-fill" style="width:{{ $product->reviews_count ? round($count / $product->reviews_count * 100) : 0 }}%;"></span></span>
                                <span style="width:20px; text-align:end;">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @forelse ($product->approvedReviews as $review)
                <div class="dj-review-card">
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px; flex-wrap:wrap;">
                        <span style="font-weight:700; font-size:14px; color:var(--dj-maroon);">{{ $review->name }}</span>
                        <span class="dj-stars" style="margin-bottom:0;">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</span>
                        @if ($review->is_verified_purchase)
                            <span style="font-size:11px; color:#237a3f; background:#e3f3e6; padding:2px 8px; border-radius:999px;">{{ __('reviews.verified_purchase') }}</span>
                        @endif
                    </div>
                    @if ($review->title)
                        <p style="font-weight:600; font-size:13.5px; color:var(--dj-ink); margin-bottom:2px;">{{ $review->title }}</p>
                    @endif
                    <p style="font-size:13.5px; color:#8a6b70;">{{ $review->comment }}</p>
                    @if ($review->images->isNotEmpty())
                        <div class="dj-review-photos">
                            @foreach ($review->images as $image)
                                <img src="{{ asset('storage/'.$image->path) }}" class="dj-review-photo">
                            @endforeach
                        </div>
                    @endif
                    <p style="font-size:11.5px; color:#a68b8f; margin-top:8px;">{{ $review->created_at->translatedFormat('M j, Y') }}</p>
                    <form method="POST" action="{{ route('reviews.helpful', $review) }}">
                        @csrf
                        <button type="submit" class="dj-review-helpful">{{ __('reviews.mark_helpful') }} ({{ $review->helpful_count }})</button>
                    </form>
                </div>
            @empty
                <p style="font-size:14px; color:#8a6b70;">{{ __('reviews.be_first_to_review') }}</p>
            @endforelse

            <div style="margin-top:32px;">
                @guest
                    <a href="{{ route('login', ['redirect' => route('shop.show', $product)]) }}" class="dj-modal-add" style="display:inline-block; text-align:center; text-decoration:none;">{{ __('reviews.login_to_review') }}</a>
                @else
                    @if ($userReview && in_array($userReview->status, ['approved', 'rejected'], true))
                        <div class="dj-review-card" style="border:1px solid var(--dj-cream-2); border-radius:14px; padding:16px;">
                            <p style="font-weight:700; font-size:14px; color:var(--dj-maroon); margin-bottom:4px;">{{ __('reviews.your_review') }}</p>
                            <span class="dj-stars">{{ str_repeat('★', $userReview->rating) }}{{ str_repeat('☆', 5 - $userReview->rating) }}</span>
                            <p style="font-size:13.5px; color:#8a6b70;">{{ $userReview->comment }}</p>
                            @if ($userReview->status === 'rejected')
                                <p style="font-size:12px; color:#b42318; margin-top:6px;">{{ __('reviews.locked_notice') }}</p>
                                @if ($userReview->rejection_reason)
                                    <p style="font-size:12px; color:#b42318;">{{ __('reviews.rejection_reason_label') }}: {{ $userReview->rejection_reason }}</p>
                                @endif
                            @endif
                            <form method="POST" action="{{ route('reviews.destroy', $userReview) }}" onsubmit="return confirm('{{ __('reviews.confirm_delete') }}')" style="margin-top:10px;">
                                @csrf @method('DELETE')
                                <button type="submit" style="font-size:12.5px; color:#b42318; text-decoration:underline;">{{ __('reviews.delete_review') }}</button>
                            </form>
                        </div>
                    @else
                        <h3 style="font-size:18px; color:var(--dj-maroon); margin-bottom:14px;">{{ $userReview ? __('reviews.edit_review') : __('reviews.write_review') }}</h3>

                        @if ($userReview)
                            <p style="font-size:12.5px; color:#8a5a2a; background:rgba(232,195,154,.35); padding:8px 12px; border-radius:8px; margin-bottom:14px;">{{ __('reviews.pending_notice') }}</p>
                        @endif

                        <form method="POST"
                              action="{{ $userReview ? route('reviews.update', $userReview) : route('reviews.store', $product) }}"
                              enctype="multipart/form-data">
                            @csrf
                            @if ($userReview) @method('PATCH') @endif

                            <p style="font-size:12.5px; color:#8a6b70; margin-bottom:6px;">{{ __('reviews.your_rating') }}</p>
                            <div class="dj-star-picker" style="margin-bottom:16px;">
                                @for ($i = 5; $i >= 1; $i--)
                                    <input type="radio" name="rating" id="dj-review-star-{{ $i }}" value="{{ $i }}" {{ old('rating', $userReview->rating ?? 0) == $i ? 'checked' : '' }} required>
                                    <label for="dj-review-star-{{ $i }}">★</label>
                                @endfor
                            </div>
                            @error('rating') <p style="color:var(--dj-rose-dust); font-size:12px; margin-top:-10px; margin-bottom:10px;">{{ $message }}</p> @enderror

                            <input type="text" name="title" value="{{ old('title', $userReview->title ?? '') }}" placeholder="{{ __('reviews.title_label') }}" maxlength="150"
                                   style="width:100%; padding:12px 14px; border:1px solid var(--dj-cream-2); border-radius:10px; font-size:13.5px; margin-bottom:12px;">

                            <textarea name="comment" rows="4" minlength="10" maxlength="1000" required placeholder="{{ __('reviews.comment_placeholder') }}"
                                      style="width:100%; padding:12px 14px; border:1px solid var(--dj-cream-2); border-radius:10px; font-size:13.5px; margin-bottom:6px;">{{ old('comment', $userReview->comment ?? '') }}</textarea>
                            @error('comment') <p style="color:var(--dj-rose-dust); font-size:12px; margin-bottom:10px;">{{ $message }}</p> @enderror

                            <label style="display:block; font-size:12.5px; color:#8a6b70; margin-bottom:16px;">
                                {{ __('reviews.photos_label') }}
                                <input type="file" name="photos[]" accept="image/*" multiple style="display:block; margin-top:6px; font-size:12.5px;">
                            </label>
                            @error('photos.*') <p style="color:var(--dj-rose-dust); font-size:12px; margin-bottom:10px;">{{ $message }}</p> @enderror

                            <button type="submit" class="dj-modal-add" style="width:auto; padding:14px 28px;">
                                {{ $userReview ? __('reviews.update_review') : __('reviews.submit') }}
                            </button>
                        </form>

                        @if ($userReview)
                            <form method="POST" action="{{ route('reviews.destroy', $userReview) }}" onsubmit="return confirm('{{ __('reviews.confirm_delete') }}')" style="margin-top:12px;">
                                @csrf @method('DELETE')
                                <button type="submit" style="font-size:12.5px; color:#b42318; text-decoration:underline;">{{ __('reviews.delete_review') }}</button>
                            </form>
                        @endif
                    @endif
                @endguest
            </div>
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

    @if ($recommendedProducts->isNotEmpty())
        <div class="max-w-5xl mx-auto px-4 sm:px-6">
            <div class="dj-section-title" style="text-align:left; padding:20px 0;">
                <h2 style="font-size:24px;">{{ __('More from') }} {{ trans_field($product->brand, 'name') }}</h2>
            </div>
        </div>
        <div class="dj-grid" style="padding-top:0;">
            @foreach ($recommendedProducts as $recommended)
                @include('shop.partials.product-card', ['product' => $recommended])
            @endforeach
        </div>
    @endif

    {{-- Guarded: #dj-pdp-form only renders when the product isn't in the
         whole-product-out-of-stock state (see the @if above) — dereferencing
         it unconditionally here would throw on that branch and break every
         script in this block, including the size/quantity selector. --}}
    @if (! $wholeProductOutOfStock)
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
            const addBtn = document.getElementById('dj-pdp-add-btn');
            addBtn.disabled = true;
            addBtn.classList.add('dj-btn-loading');
            try {
                await djAddToCart(
                    djPdpForm.dataset.addUrl, selected.dataset.size, djPdpQty,
                    '{{ __('Added to cart ✓') }}', '{{ __('Could not add this item.') }}',
                    { id: {{ $product->id }}, name: @json(trans_field($product, 'name')), price: {{ $product->price }} }
                );
            } finally {
                addBtn.classList.remove('dj-btn-loading');
                djPdpRefreshStockUi();
            }
        }

        djPdpRefreshStockUi();
    </script>
    @endif

    <script>
        window.djTrack && window.djTrack('view_item', {
            id: {{ $product->id }},
            name: @json(trans_field($product, 'name')),
            price: {{ $product->price }},
        });
    </script>
@endsection
