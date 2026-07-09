@extends('emails.layouts.master')

@php
    $icon = 'warning-triangle';
    $headerTagline = __('emails.payment_failed_tagline');
@endphp

@section('content')
    <h2 style="font-size:20px; color:#601526; margin:0 0 4px; font-family: Georgia, 'Times New Roman', serif; text-align:center;">
        {{ __('emails.payment_failed_greeting', ['name' => $order->customer_name]) }}
    </h2>

    <p style="font-size:14px; line-height:1.8; color:#5a4448; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif; text-align:center; margin:0 0 22px;">
        {{ __('emails.payment_failed_intro', ['number' => $order->order_number]) }}
    </p>

    @if ($reason)
        <p style="font-size:13.5px; line-height:1.7; color:#9C5064; background:#F7EFE4; border-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}:3px solid #601526; border-radius:10px; padding:14px 18px; margin:0 0 22px; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif;">
            {{ $reason }}
        </p>
    @endif

    <p style="font-size:13px; color:#5a4448; text-align:center; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif;">
        {{ __('emails.payment_failed_note') }}
    </p>

    @include('emails.partials.button', ['href' => route('checkout.success', $order), 'label' => __('emails.payment_failed_button')])
@endsection
