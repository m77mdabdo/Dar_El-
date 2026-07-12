@extends('emails.layouts.master')

@php
    $icon = 'warning-triangle';
    $headerTagline = __('emails.review_rejected_tagline');
@endphp

@section('content')
    <h2 style="font-size:20px; color:#601526; margin:0 0 4px; font-family: Georgia, 'Times New Roman', serif; text-align:center;">
        {{ __('emails.review_rejected_greeting', ['name' => $review->user->name]) }}
    </h2>

    <p style="font-size:14px; line-height:1.8; color:#5a4448; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif; text-align:center; margin:0 0 22px;">
        {{ __('emails.review_rejected_intro', ['product' => trans_field($review->product, 'name')]) }}
    </p>

    @if ($review->rejection_reason)
        <p style="font-size:13.5px; line-height:1.7; color:#9C5064; background:#F7EFE4; border-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}:3px solid #601526; border-radius:10px; padding:14px 18px; margin:0 0 22px; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif;">
            {{ __('emails.review_rejected_reason', ['reason' => $review->rejection_reason]) }}
        </p>
    @endif

    @include('emails.partials.button', ['href' => route('shop.show', $review->product), 'label' => __('emails.review_view_button')])
@endsection
