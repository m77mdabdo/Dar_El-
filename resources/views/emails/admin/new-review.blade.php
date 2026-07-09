@extends('emails.layouts.master')

@php
    $icon = 'star';
    $headerTagline = __('emails.admin_tagline');
@endphp

@section('content')
    <p style="font-size:14px; line-height:1.8; color:#5a4448; font-family:sans-serif;">
        {{ __('emails.admin_new_review_intro', ['product' => $review->product->name_en]) }}
    </p>

    <p style="font-size:13.5px; color:#9C5064; font-family:sans-serif;">
        {{ __('emails.admin_new_review_rating') }}: {{ $review->rating }}/5
    </p>

    @include('emails.partials.button', ['href' => route('admin.reviews.show', $review), 'label' => __('emails.admin_new_review_button')])
@endsection
