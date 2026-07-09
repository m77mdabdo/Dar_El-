@extends('emails.layouts.master')

@php
    $djIsRtl = app()->getLocale() === 'ar';
    $icon = 'heart';
    $headerTagline = __('emails.wishlist_reminder_tagline');
@endphp

@section('content')
    <h2 style="font-size:18px; color:#601526; margin-top:0; font-family:sans-serif;">
        {{ __('emails.wishlist_reminder_greeting', ['name' => $user->name]) }}
    </h2>

    <p style="font-size:14px; line-height:1.8; color:#5a4448; font-family:sans-serif;">
        {{ __('emails.wishlist_reminder_intro') }}
    </p>

    <table style="width:100%; border-collapse:collapse; margin:20px 0;">
        <tbody>
            @foreach ($wishlists as $wishlist)
                <tr style="border-bottom:1px solid #EFE2CE;">
                    <td style="padding:10px 0; width:56px;">
                        @if ($wishlist->product?->cover_image_src)
                            <img src="{{ $wishlist->product->cover_image_src }}" width="48" height="48" style="border-radius:8px; object-fit:cover;" alt="">
                        @endif
                    </td>
                    <td style="padding:10px 8px;">
                        <div style="font-size:13.5px; font-weight:600; color:#2A1015; font-family:sans-serif;">{{ $wishlist->product?->name_en }}</div>
                    </td>
                    <td style="padding:10px 0; text-align:{{ $djIsRtl ? 'left' : 'right' }}; font-size:13.5px; font-weight:600; white-space:nowrap; font-family:sans-serif;">
                        {{ number_format($wishlist->product?->price ?? 0) }} EGP
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @include('emails.partials.button', ['href' => route('wishlist.index'), 'label' => __('emails.wishlist_reminder_button')])
@endsection
