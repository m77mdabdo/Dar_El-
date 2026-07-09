@extends('emails.layouts.master')

@php
    $icon = 'warning-triangle';
    $headerTagline = __('emails.payment_failed_tagline');
@endphp

@section('content')
    <h2 style="font-size:18px; color:#601526; margin-top:0; font-family:sans-serif;">
        {{ __('emails.payment_failed_greeting', ['name' => $order->customer_name]) }}
    </h2>

    <p style="font-size:14px; line-height:1.8; color:#5a4448; font-family:sans-serif;">
        {{ __('emails.payment_failed_intro', ['number' => $order->order_number]) }}
    </p>

    @if ($reason)
        <p style="font-size:13.5px; line-height:1.7; color:#9C5064; background:#F7EFE4; border-radius:8px; padding:12px 16px; font-family:sans-serif;">
            {{ $reason }}
        </p>
    @endif

    <p style="font-size:13px; color:#5a4448; font-family:sans-serif;">
        {{ __('emails.payment_failed_note') }}
    </p>

    @include('emails.partials.button', ['href' => route('checkout.success', $order), 'label' => __('emails.payment_failed_button')])
@endsection
