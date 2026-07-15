@php
    $djCart = app(\App\Services\CartService::class);
    $djCartShippingFee = $djCart->estimatedShippingFee();
    $djCartTotal = $djCart->totalIncludingShipping($djCartShippingFee);
    $djCartHasIssues = ! $djCart->isValid();
@endphp
<div class="dj-drawer" id="dj-drawer">
    <div class="dj-drawer-head">
        <h3>{{ __('Shopping Cart') }}</h3>
        <button type="button" class="dj-close-btn" onclick="djCloseCart()" aria-label="{{ __('Close') }}">&times;</button>
    </div>
    <div class="dj-drawer-items" id="dj-drawer-items">
        @include('partials.cart-drawer-items', ['items' => $djCart->items()])
    </div>
    <div class="dj-drawer-foot">
        <div class="dj-total-row" id="dj-cart-shipping-row" style="{{ $djCartShippingFee > 0 ? '' : 'display:none;' }}">
            <span>{{ __('Shipping (estimated)') }}</span>
            <span id="dj-cart-shipping">{{ number_format($djCartShippingFee) }} EGP</span>
        </div>
        <div class="dj-total-row">
            <span>{{ __('Total') }}</span>
            <span id="dj-cart-total">{{ number_format($djCartTotal) }} EGP</span>
        </div>
        <a href="{{ $djCartHasIssues ? '#' : route('checkout.show') }}" class="dj-drawer-checkout-btn {{ $djCartHasIssues ? 'dj-disabled' : '' }}" {{ $djCartHasIssues ? 'aria-disabled=true' : '' }}>{{ __('Checkout') }}</a>
    </div>
</div>
