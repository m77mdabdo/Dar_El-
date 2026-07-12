@extends('layouts.storefront')

@section('title', __('Order Confirmed') . ' — Dar El-Jamila')

@section('content')
    <div class="dj-confirm-wrap">
        <div class="dj-confirm-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 6L9 17l-5-5"/></svg>
        </div>
        <h2>{{ __('Your Order Has Been Received!') }}</h2>
        <p>{{ __("Thank you for trusting Dar El-Jamila. We'll start preparing your order right away.") }}</p>
        <div class="dj-order-number">#{{ $order->order_number }}</div>
        <p>{{ __('A confirmation email with your invoice is on its way.') }}</p>

        <div style="text-align:left; background:#fff; border-radius:16px; padding:24px; margin-top:26px; box-shadow:0 10px 24px -18px rgba(60,11,23,.3);">
            @foreach ($order->items as $item)
                <div style="display:flex; justify-content:space-between; font-size:13.5px; padding:8px 0; color:#5a4448;">
                    <span>{{ $item->product ? trans_field($item->product, 'name') : $item->product_name }} ({{ $item->size }}) &times; {{ $item->quantity }}</span>
                    <span>{{ number_format($item->price * $item->quantity) }} EGP</span>
                </div>
            @endforeach
            <div class="dj-os-row dj-total" style="margin-top:10px;">
                <span>{{ __('Total') }}</span>
                <span>{{ number_format($order->total) }} EGP</span>
            </div>
        </div>

        <div style="text-align:left; background:#fff; border-radius:16px; padding:24px; margin-top:16px; box-shadow:0 10px 24px -18px rgba(60,11,23,.3);">
            <div style="display:flex; justify-content:space-between; font-size:13.5px; padding:8px 0; color:#5a4448;">
                <span>{{ __('Payment Method') }}</span>
                <span>{{ $order->payment_method === \App\Models\Order::PAYMENT_METHOD_COD ? __('Cash on Delivery') : $order->payment_method }}</span>
            </div>
            <div style="display:flex; justify-content:space-between; font-size:13.5px; padding:8px 0; color:#5a4448;">
                <span>{{ __('Shipping Method') }}</span>
                <span>{{ $order->shipping_method_name ?? __('Standard Delivery') }}</span>
            </div>
            @if ($order->shipping_delivery_min_days)
                <div style="display:flex; justify-content:space-between; font-size:13.5px; padding:8px 0; color:#5a4448;">
                    <span>{{ __('Estimated Delivery') }}</span>
                    <span>
                        @if ($order->shipping_delivery_min_days === $order->shipping_delivery_max_days)
                            {{ $order->shipping_delivery_min_days }} {{ __('days') }}
                        @else
                            {{ $order->shipping_delivery_min_days }}–{{ $order->shipping_delivery_max_days }} {{ __('days') }}
                        @endif
                    </span>
                </div>
            @endif
            <div style="display:flex; justify-content:space-between; font-size:13.5px; padding:8px 0; color:#5a4448; gap:16px;">
                <span>{{ __('Delivery Address') }}</span>
                <span style="text-align:end;">{{ $order->address }}, {{ $order->city }}, {{ $order->governorate }}</span>
            </div>
        </div>

        <div style="margin-top:30px; display:flex; gap:14px; justify-content:center; flex-wrap:wrap;">
            <a href="{{ route('shop.index') }}" class="dj-hero-cta" style="position:relative;">{{ __('Continue Shopping') }}</a>
            @auth
                <a href="{{ route('account.orders.show', $order) }}" class="dj-hero-cta" style="position:relative;">{{ __('View Order') }}</a>
            @endauth
        </div>
    </div>
@endsection
