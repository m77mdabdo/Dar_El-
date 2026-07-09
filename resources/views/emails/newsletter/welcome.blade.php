@extends('emails.layouts.master')

@php
    $icon = 'envelope';
    $headerTagline = __('emails.newsletter_welcome_tagline');
@endphp

@section('content')
    <h2 style="font-size:18px; color:#601526; margin-top:0; font-family:sans-serif;">
        {{ __('emails.newsletter_welcome_greeting') }}
    </h2>

    <p style="font-size:14px; line-height:1.8; color:#5a4448; font-family:sans-serif;">
        {{ __('emails.newsletter_welcome_intro') }}
    </p>

    @include('emails.partials.button', ['href' => route('shop.index'), 'label' => __('emails.newsletter_welcome_button')])
@endsection
