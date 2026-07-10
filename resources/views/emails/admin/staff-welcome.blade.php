@extends('emails.layouts.master')

@php
    $icon = 'user';
    $headerTagline = __('emails.admin_tagline');
@endphp

@section('content')
    <h2 style="font-size:20px; color:#601526; margin:0 0 4px; font-family: Georgia, 'Times New Roman', serif; text-align:center;">
        {{ __('emails.admin_user_welcome_greeting', ['name' => $user->name]) }}
    </h2>

    <p style="font-size:14px; line-height:1.8; color:#5a4448; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif; text-align:center; margin:0 0 26px;">
        {{ __('emails.admin_user_welcome_intro', ['role' => $roleLabel]) }}
    </p>

    @include('emails.partials.info-card', [
        'djIcon' => 'user',
        'djTitle' => __('emails.admin_user_welcome_credentials_title'),
        'djRows' => [
            ['label' => __('emails.admin_new_customer_email'), 'value' => $user->email],
            ['label' => __('emails.admin_user_welcome_password'), 'value' => $temporaryPassword],
        ],
    ])

    <p style="font-size:13px; color:#9C5064; text-align:center; margin:0 0 26px; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif;">
        {{ __('emails.admin_user_welcome_note') }}
    </p>

    @include('emails.partials.button', ['href' => route('login'), 'label' => __('emails.admin_user_welcome_button')])
@endsection
