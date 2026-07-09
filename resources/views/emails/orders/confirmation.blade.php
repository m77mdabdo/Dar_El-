@extends('emails.layouts.master')

@php
    $icon = 'bag';
    $djIsRtl = app()->getLocale() === 'ar';
    $headerTagline = __('emails.order_confirmation_tagline');
@endphp

@section('content')
    <h2 style="font-size:18px; color:#601526; margin-top:0; font-family:sans-serif;">
        {{ __('emails.order_confirmation_greeting', ['name' => $order->customer_name]) }}
    </h2>

    <p style="font-size:14px; line-height:1.8; color:#5a4448; font-family:sans-serif;">
        {{ __('emails.order_confirmation_intro', ['number' => $order->order_number, 'invoice' => $invoice->invoice_number]) }}
    </p>

    <table style="width:100%; border-collapse:collapse; margin:20px 0;">
        <tbody>
            @foreach ($order->items as $item)
                <tr style="border-bottom:1px solid #EFE2CE;">
                    <td style="padding:10px 0; width:56px;">
                        @if ($item->product?->cover_image_src)
                            <img src="{{ $item->product->cover_image_src }}" width="48" height="48" style="border-radius:8px; object-fit:cover;" alt="">
                        @endif
                    </td>
                    <td style="padding:10px 8px;">
                        <div style="font-size:13.5px; font-weight:600; color:#2A1015; font-family:sans-serif;">{{ $item->product_name }}</div>
                        @if ($item->size)
                            <div style="font-size:12px; color:#9C5064; font-family:sans-serif;">{{ __('emails.order_variant') }}: {{ $item->size }}</div>
                        @endif
                        <div style="font-size:12px; color:#9C5064; font-family:sans-serif;">{{ __('emails.order_qty') }}: {{ $item->quantity }}</div>
                    </td>
                    <td style="padding:10px 0; text-align:{{ $djIsRtl ? 'left' : 'right' }}; font-size:13.5px; font-weight:600; white-space:nowrap; font-family:sans-serif;">
                        {{ number_format($item->price * $item->quantity) }} EGP
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table style="width:100%; border-collapse:collapse; font-size:13.5px; font-family:sans-serif;">
        <tr>
            <td style="padding:4px 0; color:#5a4448;">{{ __('emails.order_subtotal') }}</td>
            <td style="padding:4px 0; text-align:{{ $djIsRtl ? 'left' : 'right' }};">{{ number_format($order->subtotal) }} EGP</td>
        </tr>
        @if ($order->discount_amount > 0)
            <tr>
                <td style="padding:4px 0; color:#5a4448;">{{ __('emails.order_discount') }}</td>
                <td style="padding:4px 0; text-align:{{ $djIsRtl ? 'left' : 'right' }};">-{{ number_format($order->discount_amount) }} EGP</td>
            </tr>
        @endif
        <tr>
            <td style="padding:4px 0; color:#5a4448;">{{ __('emails.order_shipping_fee') }}</td>
            <td style="padding:4px 0; text-align:{{ $djIsRtl ? 'left' : 'right' }};">{{ number_format($order->shipping_fee) }} EGP</td>
        </tr>
        <tr>
            <td style="padding:10px 0 4px; font-size:16px; font-weight:700; color:#601526;">{{ __('emails.order_grand_total') }}</td>
            <td style="padding:10px 0 4px; text-align:{{ $djIsRtl ? 'left' : 'right' }}; font-size:16px; font-weight:700; color:#601526;">{{ number_format($order->total) }} EGP</td>
        </tr>
    </table>

    <table style="width:100%; border-collapse:collapse; margin-top:20px; font-size:13px; font-family:sans-serif;">
        <tr>
            <td style="padding:4px 0; color:#9C5064; width:40%;">{{ __('emails.order_payment_method') }}</td>
            <td style="padding:4px 0; color:#2A1015;">{{ $order->payment_method === 'cod' ? __('emails.order_payment_method_cod') : $order->payment_method }}</td>
        </tr>
        <tr>
            <td style="padding:4px 0; color:#9C5064; vertical-align:top;">{{ __('emails.order_shipping_address') }}</td>
            <td style="padding:4px 0; color:#2A1015;">{{ $order->address }}, {{ $order->city }}, {{ $order->governorate }}</td>
        </tr>
    </table>

    @include('emails.partials.button', ['href' => route('checkout.success', $order), 'label' => __('emails.order_view_button')])
@endsection
