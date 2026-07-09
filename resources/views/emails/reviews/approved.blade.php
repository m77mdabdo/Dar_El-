@extends('emails.layouts.master')

@php
    $icon = 'check-circle';
    $headerTagline = __('emails.review_approved_tagline');
@endphp

@section('content')
    <h2 style="font-size:18px; color:#601526; margin-top:0; font-family:sans-serif;">
        {{ __('emails.review_approved_greeting', ['name' => $review->user->name]) }}
    </h2>

    <p style="font-size:14px; line-height:1.8; color:#5a4448; font-family:sans-serif;">
        {{ __('emails.review_approved_intro', ['product' => $review->product->name_en]) }}
    </p>

    @include('emails.partials.button', ['href' => route('shop.show', $review->product), 'label' => __('emails.review_view_button')])
@endsection
