@extends('emails.layouts.master')

@php
    $icon = 'shield';
    $headerTagline = __('emails.password_reset_tagline');
    $securityNote = __('emails.ignore_note');
@endphp

@section('content')
    <h2 style="font-size:20px; color:#601526; margin:0 0 4px; font-family: Georgia, 'Times New Roman', serif; text-align:center;">
        {{ __('emails.password_reset_greeting', ['name' => $user->name]) }}
    </h2>

    <p style="font-size:14px; line-height:1.8; color:#5a4448; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif; text-align:center; margin:0 0 8px;">
        {{ __('emails.password_reset_intro') }}
    </p>

    @include('emails.partials.button', ['href' => $url, 'label' => __('emails.password_reset_button')])

    <p style="font-size:13px; color:#9C5064; text-align:center; margin:24px 0 0; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif;">
        {{ __('emails.password_reset_expires', ['minutes' => $expiresInMinutes]) }}
    </p>
@endsection
