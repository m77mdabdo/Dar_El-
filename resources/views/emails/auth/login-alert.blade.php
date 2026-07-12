@extends('emails.layouts.master')

@php
    $icon = 'shield-device';
    $headerTagline = __('emails.login_alert_tagline');
    $securityNote = __('emails.login_alert_not_you_note');
@endphp

@section('content')
    <h2 style="font-size:20px; color:#601526; margin:0 0 4px; font-family: Georgia, 'Times New Roman', serif; text-align:center;">
        {{ __('emails.login_alert_greeting', ['name' => $user->name]) }}
    </h2>

    <p style="font-size:14px; line-height:1.8; color:#5a4448; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif; text-align:center; margin:0 0 26px;">
        {{ __('emails.login_alert_intro') }}
    </p>

    @include('emails.partials.info-card', [
        'djIcon' => 'shield',
        'djTitle' => __('emails.login_alert_tagline'),
        'djRows' => array_filter([
            ['label' => __('emails.login_alert_email'), 'value' => $user->email],
            ['label' => __('emails.login_alert_time'), 'value' => $time->translatedFormat('M j, Y H:i')],
            ['label' => __('emails.login_alert_ip'), 'value' => $ip],
            ['label' => __('emails.login_alert_device'), 'value' => $device],
            ['label' => __('emails.login_alert_browser'), 'value' => $browser],
            ($provider ?? null) ? ['label' => __('emails.login_alert_provider'), 'value' => ucfirst($provider)] : null,
        ]),
    ])

    <p style="font-size:13px; color:#5a4448; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif; text-align:center;">
        {{ __('emails.login_alert_ok_note') }}
    </p>

    @include('emails.partials.button', ['href' => route('password.request'), 'label' => __('emails.login_alert_reset_button')])
@endsection
