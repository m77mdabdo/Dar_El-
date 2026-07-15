@extends('layouts.storefront')

@section('title', __('Your Cart') . ' — Dar El Jamila')

@section('content')
    <div class="max-w-5xl mx-auto px-4 sm:px-6 py-12">
        <h1 style="font-size:28px; color:var(--dj-maroon); margin-bottom:30px;">{{ __('Your Cart') }}</h1>

        @if (empty($items))
            <div class="dj-empty-cart" style="text-align:center;">
                🛍️<br>{{ __('Your cart is empty.') }}
                <br><a href="{{ route('shop.index') }}" style="color:var(--dj-maroon); text-decoration:underline;">{{ __('Continue shopping') }}</a>
            </div>
        @else
            <div style="display:flex; flex-direction:column; gap:16px;">
                @foreach ($items as $item)
                    <div style="display:flex; flex-wrap:wrap; align-items:center; gap:16px; background:#fff; border-radius:14px; padding:16px; box-shadow:0 8px 20px -16px rgba(60,11,23,.3); {{ $item['exceeds_stock'] ? 'border:1.5px solid var(--dj-rose-dust);' : '' }}">
                        <div class="dj-photo-wrap dj-tint-maroon" style="width:80px; height:80px; border-radius:10px; overflow:hidden; flex-shrink:0;">
                            @if ($item['product']->cover_image_src)
                                <img src="{{ $item['product']->cover_image_src }}" alt="">
                            @endif
                        </div>
                        <div style="flex:1; min-width:150px;">
                            <h3 style="font-weight:700; font-size:14.5px; color:var(--dj-ink);">{{ trans_field($item['product'], 'name') }}</h3>
                            <p style="font-size:12.5px; color:#8a6b70;">{{ __('Size') }}: {{ $item['size'] }} · {{ number_format($item['product']->price) }} EGP</p>
                            @if ($item['exceeds_stock'])
                                <p style="font-size:12px; color:var(--dj-rose-dust); font-weight:700; margin-top:4px;">
                                    {{ $item['stock'] > 0 ? __('Only :count left — please lower the quantity.', ['count' => $item['stock']]) : __('This size just sold out.') }}
                                </p>
                            @endif
                        </div>
                        <form method="POST" action="{{ route('cart.update', $item['key']) }}">
                            @csrf
                            @method('PATCH')
                            <input type="number" name="quantity" value="{{ $item['quantity'] }}" min="0" max="20" onchange="this.form.submit()" style="width:64px; border:1.5px solid {{ $item['exceeds_stock'] ? 'var(--dj-rose-dust)' : 'var(--dj-cream-2)' }}; border-radius:8px; padding:6px 8px; font-size:13px;">
                        </form>
                        <p style="width:90px; text-align:right; font-weight:700; color:var(--dj-maroon);">{{ number_format($item['subtotal']) }} EGP</p>
                        <form method="POST" action="{{ route('cart.remove', $item['key']) }}">
                            @csrf
                            @method('DELETE')
                            <button style="width:36px; height:36px; color:var(--dj-rose-dust); font-size:18px;" title="{{ __('Remove') }}">&times;</button>
                        </form>
                    </div>
                @endforeach
            </div>

            <div class="dj-order-summary" style="max-width:360px; margin-inline-start:auto; margin-top:30px;">
                <form method="POST" action="{{ route('cart.coupon.apply') }}" style="display:flex; gap:8px; margin-bottom:16px;">
                    @csrf
                    <input type="text" name="code" placeholder="{{ __('Coupon code') }}" aria-label="{{ __('Coupon code') }}" value="{{ $coupon->code ?? '' }}" style="flex:1; border:1.5px solid var(--dj-cream-2); border-radius:8px; padding:8px 12px; font-size:13px;">
                    <button style="background:var(--dj-maroon); color:var(--dj-gold); font-size:13px; padding:8px 16px; border-radius:8px;">{{ __('Apply') }}</button>
                </form>
                @if ($coupon)
                    <form method="POST" action="{{ route('cart.coupon.remove') }}" style="margin-bottom:16px;">
                        @csrf
                        @method('DELETE')
                        <button style="font-size:12px; color:var(--dj-rose-dust); text-decoration:underline;">{{ __('Remove coupon') }}</button>
                    </form>
                @endif

                <div class="dj-os-row"><span>{{ __('Subtotal') }}</span><span>{{ number_format($subtotal) }} EGP</span></div>
                @if ($discount > 0)
                    <div class="dj-os-row" style="color:#2f7a4d;"><span>{{ __('Discount') }}</span><span>-{{ number_format($discount) }} EGP</span></div>
                @endif
                @if ($shippingFee > 0)
                    <div class="dj-os-row"><span>{{ __('Shipping (estimated)') }}</span><span>{{ number_format($shippingFee) }} EGP</span></div>
                @endif
                <div class="dj-os-row dj-total"><span>{{ __('Total') }}</span><span>{{ number_format($total) }} EGP</span></div>

                @if ($hasStockIssues)
                    <p style="font-size:12.5px; color:var(--dj-rose-dust); font-weight:700; margin-bottom:12px; text-align:center;">
                        {{ __('Please resolve the stock issues above before checking out.') }}
                    </p>
                @endif
                <a href="{{ $hasStockIssues ? '#' : route('checkout.show') }}" class="dj-place-order-btn" style="display:block; text-align:center; text-decoration:none; {{ $hasStockIssues ? 'opacity:.5; pointer-events:none; cursor:not-allowed;' : '' }}">{{ __('Proceed to Checkout') }}</a>
            </div>
        @endif
    </div>
@endsection
