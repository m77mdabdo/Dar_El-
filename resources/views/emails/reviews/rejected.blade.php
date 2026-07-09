@extends('emails.layouts.master')

@php
    $icon = 'warning-triangle';
    $headerTagline = __('emails.review_rejected_tagline');
@endphp

@section('content')
    <h2 style="font-size:18px; color:#601526; margin-top:0; font-family:sans-serif;">
        {{ __('emails.review_rejected_greeting', ['name' => $review->user->name]) }}
    </h2>

    <p style="font-size:14px; line-height:1.8; color:#5a4448; font-family:sans-serif;">
        {{ __('emails.review_rejected_intro', ['product' => $review->product->name_en]) }}
    </p>

    @if ($review->rejection_reason)
        <p style="font-size:13.5px; line-height:1.7; color:#9C5064; background:#F7EFE4; border-radius:8px; padding:12px 16px; font-family:sans-serif;">
            {{ __('emails.review_rejected_reason', ['reason' => $review->rejection_reason]) }}
        </p>
    @endif

    @include('emails.partials.button', ['href' => route('shop.show', $review->product), 'label' => __('emails.review_view_button')])
@endsection
