@extends('emails.layouts.master')

@php
    $icon = 'check-circle';
    $headerTagline = __('emails.back_in_stock_tagline');
@endphp

@section('content')
    <h2 style="font-size:18px; color:#601526; margin-top:0; font-family:sans-serif;">
        {{ __('emails.back_in_stock_greeting', ['name' => $user->name]) }}
    </h2>

    <p style="font-size:14px; line-height:1.8; color:#5a4448; font-family:sans-serif;">
        {{ __('emails.back_in_stock_intro', ['product' => $product->name_en]) }}
    </p>

    <div style="text-align:center; margin:20px 0;">
        @if ($product->cover_image_src)
            <img src="{{ $product->cover_image_src }}" width="120" height="120" style="border-radius:12px; object-fit:cover;" alt="">
        @endif
    </div>

    @include('emails.partials.button', ['href' => route('shop.show', $product), 'label' => __('emails.back_in_stock_button')])
@endsection
