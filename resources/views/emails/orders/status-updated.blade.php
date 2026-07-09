@extends('emails.layouts.master')

@php
    $icon = 'bag';
    $headerTagline = __('emails.order_status_tagline');
@endphp

@section('content')
    <h2 style="font-size:18px; color:#601526; margin-top:0; font-family:sans-serif;">
        {{ __('emails.order_status_greeting', ['name' => $order->customer_name]) }}
    </h2>

    <p style="font-size:14px; line-height:1.8; color:#5a4448; font-family:sans-serif;">
        {{ __('emails.order_status_intro', ['number' => $order->order_number]) }}
    </p>

    <div style="text-align:center; margin:24px 0;">
        <span style="display:inline-block; background:#F7EFE4; border:1px solid #EFE2CE; border-radius:999px; padding:10px 24px; font-size:15px; font-weight:700; color:#601526; font-family:sans-serif;">
            {{ __('orders.status_'.$order->status) }}
        </span>
    </div>

    @include('emails.partials.button', ['href' => route('account.orders.show', $order), 'label' => __('emails.order_view_button')])

    <p style="font-size:13px; color:#9C5064; text-align:center; margin-top:20px; font-family:sans-serif;">
        {{ __('emails.order_status_thanks') }}
    </p>
@endsection
