@extends('emails.layouts.master')

@php
    $icon = 'bag';
    $headerTagline = __('emails.cart_reminder_tagline');
@endphp

@section('content')
    <h2 style="font-size:20px; color:#601526; margin:0 0 4px; font-family: Georgia, 'Times New Roman', serif; text-align:center;">
        {{ __('emails.cart_reminder_greeting', ['name' => $cart->user->name]) }}
    </h2>

    <p style="font-size:14px; line-height:1.8; color:#5a4448; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif; text-align:center; margin:0 0 30px;">
        {{ __('emails.cart_reminder_intro') }}
    </p>

    @include('emails.partials.product-card', [
        'djRows' => $cart->items->map(fn ($item) => [
            'image' => $item->image_snapshot,
            'name' => $item->product_name,
            'meta' => array_filter([
                ! empty($item->variant_snapshot['size']) ? __('carts.size').': '.$item->variant_snapshot['size'] : null,
                __('carts.quantity').': '.$item->quantity,
            ]),
            'price' => number_format($item->total).' EGP',
        ])->all(),
    ])

    @include('emails.partials.summary-box', [
        'djRows' => [],
        'djTotal' => ['label' => __('carts.cart_total'), 'value' => number_format($cart->total).' EGP'],
    ])

    @include('emails.partials.button', ['href' => route('cart.index'), 'label' => __('carts.continue_checkout')])
@endsection
