@extends('emails.layouts.master')

@php
    $djIsRtl = app()->getLocale() === 'ar';
    $icon = 'credit-card';
    $headerTagline = __('emails.payment_success_tagline');
@endphp

@section('content')
    <h2 style="font-size:18px; color:#601526; margin-top:0; font-family:sans-serif;">
        {{ __('emails.payment_success_greeting', ['name' => $order->customer_name]) }}
    </h2>

    <p style="font-size:14px; line-height:1.8; color:#5a4448; font-family:sans-serif;">
        {{ __('emails.payment_success_intro', ['number' => $order->order_number]) }}
    </p>

    <table style="width:100%; border-collapse:collapse; margin:16px 0; font-size:13.5px; font-family:sans-serif;">
        <tr>
            <td style="padding:4px 0; color:#9C5064; width:40%;">{{ __('orders.order_number') }}</td>
            <td style="padding:4px 0; color:#2A1015; font-weight:600;">{{ $order->order_number }}</td>
        </tr>
        <tr>
            <td style="padding:4px 0; color:#9C5064;">{{ __('emails.payment_success_amount') }}</td>
            <td style="padding:4px 0; color:#601526; font-weight:700;">{{ number_format($amount) }} EGP</td>
        </tr>
    </table>

    @include('emails.partials.button', ['href' => route('checkout.success', $order), 'label' => __('emails.payment_success_button')])
@endsection
