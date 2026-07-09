@extends('emails.layouts.master')

@php
    $icon = 'bag';
    $djIsRtl = app()->getLocale() === 'ar';
    $headerTagline = __('emails.cart_reminder_tagline');
@endphp

@section('content')
    <h2 style="font-size:18px; color:#601526; margin-top:0; font-family:sans-serif;">
        {{ __('emails.cart_reminder_greeting', ['name' => $cart->user->name]) }}
    </h2>

    <p style="font-size:14px; line-height:1.8; color:#5a4448; font-family:sans-serif;">
        {{ __('emails.cart_reminder_intro') }}
    </p>

    <table style="width:100%; border-collapse:collapse; margin:20px 0;">
        <tbody>
            @foreach ($cart->items as $item)
                <tr style="border-bottom:1px solid #EFE2CE;">
                    <td style="padding:10px 0; width:56px;">
                        @if ($item->image_snapshot)
                            <img src="{{ $item->image_snapshot }}" width="48" height="48" style="border-radius:8px; object-fit:cover;" alt="">
                        @endif
                    </td>
                    <td style="padding:10px 8px;">
                        <div style="font-size:13.5px; font-weight:600; color:#2A1015; font-family:sans-serif;">{{ $item->product_name }}</div>
                        @if (!empty($item->variant_snapshot['size']))
                            <div style="font-size:12px; color:#9C5064; font-family:sans-serif;">{{ __('carts.size') }}: {{ $item->variant_snapshot['size'] }}</div>
                        @endif
                        <div style="font-size:12px; color:#9C5064; font-family:sans-serif;">{{ __('carts.quantity') }}: {{ $item->quantity }}</div>
                    </td>
                    <td style="padding:10px 0; text-align:{{ $djIsRtl ? 'left' : 'right' }}; font-size:13.5px; font-weight:600; white-space:nowrap; font-family:sans-serif;">
                        {{ number_format($item->total) }} EGP
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p style="font-size:16px; font-weight:700; color:#601526; text-align:{{ $djIsRtl ? 'left' : 'right' }}; font-family:sans-serif;">
        {{ __('carts.cart_total') }}: {{ number_format($cart->total) }} EGP
    </p>

    @include('emails.partials.button', ['href' => route('cart.index'), 'label' => __('carts.continue_checkout')])
@endsection
