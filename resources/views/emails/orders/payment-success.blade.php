@extends('emails.layouts.master')

@php
    $icon = 'credit-card';
    $headerTagline = __('emails.payment_success_tagline');
@endphp

@section('content')
    <h2 style="font-size:20px; color:#601526; margin:0 0 4px; font-family: Georgia, 'Times New Roman', serif; text-align:center;">
        {{ __('emails.payment_success_greeting', ['name' => $order->customer_name]) }}
    </h2>

    <p style="font-size:14px; line-height:1.8; color:#5a4448; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif; text-align:center; margin:0 0 26px;">
        {{ __('emails.payment_success_intro', ['number' => $order->order_number]) }}
    </p>

    @include('emails.partials.info-card', [
        'djIcon' => 'wallet',
        'djTitle' => __('emails.payment_success_tagline'),
        'djRows' => [
            ['label' => __('orders.order_number'), 'value' => $order->order_number],
            ['label' => __('emails.payment_success_amount'), 'value' => number_format($amount).' EGP'],
        ],
    ])

    @include('emails.partials.button', ['href' => route('checkout.success', $order), 'label' => __('emails.payment_success_button')])
@endsection
