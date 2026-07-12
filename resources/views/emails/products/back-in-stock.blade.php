@extends('emails.layouts.master')

@php
    $icon = 'check-circle';
    $headerTagline = __('emails.back_in_stock_tagline');
@endphp

@section('content')
    <h2 style="font-size:20px; color:#601526; margin:0 0 4px; font-family: Georgia, 'Times New Roman', serif; text-align:center;">
        {{ __('emails.back_in_stock_greeting', ['name' => $user->name]) }}
    </h2>

    <p style="font-size:14px; line-height:1.8; color:#5a4448; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif; text-align:center; margin:0 0 22px;">
        {{ __('emails.back_in_stock_intro', ['product' => trans_field($product, 'name')]) }}
    </p>

    @if ($product->cover_image_src)
        <div style="text-align:center; margin:0 0 28px;">
            <img src="{{ $product->cover_image_src }}" width="128" height="128" style="border-radius:14px; object-fit:cover; display:inline-block; border:1px solid #EFE2CE; box-shadow:0 12px 24px -12px rgba(60,11,23,0.28);" alt="">
        </div>
    @endif

    @include('emails.partials.button', ['href' => route('shop.show', $product), 'label' => __('emails.back_in_stock_button')])
@endsection
