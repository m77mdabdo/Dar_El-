@extends('emails.layouts.master')

@php
    $icon = 'star';
    $headerTagline = __('emails.admin_tagline');
@endphp

@section('content')
    <p style="font-size:14px; line-height:1.8; color:#5a4448; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif; text-align:center; margin:0 0 20px;">
        {{ __('emails.admin_new_review_intro', ['product' => $review->product->name_en]) }}
    </p>

    <div style="text-align:center; margin:0 0 30px;">
        <span style="display:inline-block; background:#F7EFE4; border:1px solid #EFE2CE; border-radius:999px; padding:10px 24px; font-size:13.5px; font-weight:700; color:#601526; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif;">
            {{ __('emails.admin_new_review_rating') }}: {{ $review->rating }}/5
        </span>
    </div>

    @include('emails.partials.button', ['href' => route('admin.reviews.show', $review), 'label' => __('emails.admin_new_review_button')])
@endsection
