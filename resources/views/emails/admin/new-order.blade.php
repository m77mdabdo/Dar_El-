@extends('emails.layouts.master')

@php
    $icon = 'bag';
    $headerTagline = __('emails.admin_tagline');
@endphp

@section('content')
    <p style="font-size:14px; line-height:1.8; color:#5a4448; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif; text-align:center; margin:0 0 26px;">
        {{ __('emails.admin_new_order_intro') }}
    </p>

    @include('emails.partials.info-card', [
        'djIcon' => 'document',
        'djTitle' => __('emails.order_details_title'),
        'djRows' => [
            ['label' => __('orders.order_number'), 'value' => $order->order_number],
            ['label' => __('emails.admin_new_order_customer'), 'value' => $order->customer_name],
            ['label' => __('emails.admin_new_order_total'), 'value' => number_format($order->total).' EGP'],
        ],
    ])

    @include('emails.partials.button', ['href' => route('admin.orders.show', $order), 'label' => __('emails.admin_new_order_button')])
@endsection
