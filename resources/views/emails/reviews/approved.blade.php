@extends('emails.layouts.master')

@php
    $icon = 'check-circle';
    $headerTagline = __('emails.review_approved_tagline');
@endphp

@section('content')
    <h2 style="font-size:20px; color:#601526; margin:0 0 4px; font-family: Georgia, 'Times New Roman', serif; text-align:center;">
        {{ __('emails.review_approved_greeting', ['name' => $review->user->name]) }}
    </h2>

    <p style="font-size:14px; line-height:1.8; color:#5a4448; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif; text-align:center; margin:0 0 8px;">
        {{ __('emails.review_approved_intro', ['product' => trans_field($review->product, 'name')]) }}
    </p>

    @include('emails.partials.button', ['href' => route('shop.show', $review->product), 'label' => __('emails.review_view_button')])
@endsection
