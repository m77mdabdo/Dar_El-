@extends('emails.layouts.master')

@php
    $icon = 'bag';
    $headerTagline = __('emails.order_status_tagline');
@endphp

@section('content')
    <h2 style="font-size:20px; color:#601526; margin:0 0 4px; font-family: Georgia, 'Times New Roman', serif; text-align:center;">
        {{ __('emails.order_status_greeting', ['name' => $order->customer_name]) }}
    </h2>

    <p style="font-size:14px; line-height:1.8; color:#5a4448; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif; text-align:center; margin:0 0 6px;">
        {{ __('emails.order_status_intro', ['number' => $order->order_number]) }}
    </p>

    <div style="text-align:center; margin:22px 0 30px;">
        <span style="display:inline-block; background:#F7EFE4; border:1px solid #EFE2CE; border-radius:999px; padding:11px 28px; font-size:14px; font-weight:700; letter-spacing:.5px; color:#601526; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif;">
            {{ __('orders.status_'.$order->status) }}
        </span>
    </div>

    @include('emails.partials.button', ['href' => route('account.orders.show', $order), 'label' => __('emails.order_view_button')])

    <p style="font-size:13px; color:#9C5064; text-align:center; margin:22px 0 0; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif;">
        {{ __('emails.order_status_thanks') }}
    </p>
@endsection
