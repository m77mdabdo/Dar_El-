@extends('emails.layouts.master')

@php
    $icon = 'envelope';
    $headerTagline = __('emails.newsletter_welcome_tagline');
@endphp

@section('content')
    <h2 style="font-size:20px; color:#601526; margin:0 0 4px; font-family: Georgia, 'Times New Roman', serif; text-align:center;">
        {{ __('emails.newsletter_welcome_greeting') }}
    </h2>

    <p style="font-size:14px; line-height:1.8; color:#5a4448; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif; text-align:center; margin:0 0 8px;">
        {{ __('emails.newsletter_welcome_intro') }}
    </p>

    @include('emails.partials.button', ['href' => route('shop.index'), 'label' => __('emails.newsletter_welcome_button')])
@endsection
