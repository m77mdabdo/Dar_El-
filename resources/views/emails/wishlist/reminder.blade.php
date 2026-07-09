@extends('emails.layouts.master')

@php
    $icon = 'heart';
    $headerTagline = __('emails.wishlist_reminder_tagline');
@endphp

@section('content')
    <h2 style="font-size:20px; color:#601526; margin:0 0 4px; font-family: Georgia, 'Times New Roman', serif; text-align:center;">
        {{ __('emails.wishlist_reminder_greeting', ['name' => $user->name]) }}
    </h2>

    <p style="font-size:14px; line-height:1.8; color:#5a4448; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif; text-align:center; margin:0 0 30px;">
        {{ __('emails.wishlist_reminder_intro') }}
    </p>

    @include('emails.partials.product-card', [
        'djRows' => $wishlists->map(fn ($wishlist) => [
            'image' => $wishlist->product?->cover_image_src,
            'name' => $wishlist->product?->name_en,
            'meta' => [],
            'price' => number_format($wishlist->product?->price ?? 0).' EGP',
        ])->all(),
    ])

    @include('emails.partials.button', ['href' => route('wishlist.index'), 'label' => __('emails.wishlist_reminder_button')])
@endsection
