@extends('emails.layouts.master')

@php
    $icon = 'shield-device';
    $headerTagline = __('emails.login_alert_tagline');
    $securityNote = __('emails.login_alert_not_you_note');
@endphp

@section('content')
    <h2 style="font-size:18px; color:#601526; margin-top:0; font-family:sans-serif;">
        {{ __('emails.login_alert_greeting', ['name' => $user->name]) }}
    </h2>

    <p style="font-size:14px; line-height:1.8; color:#5a4448; font-family:sans-serif;">
        {{ __('emails.login_alert_intro') }}
    </p>

    <table style="width:100%; border-collapse:collapse; margin:16px 0; font-size:13.5px; font-family:sans-serif;">
        <tr>
            <td style="padding:4px 0; color:#9C5064; width:40%;">{{ __('emails.login_alert_email') }}</td>
            <td style="padding:4px 0; color:#2A1015;">{{ $user->email }}</td>
        </tr>
        <tr>
            <td style="padding:4px 0; color:#9C5064; width:40%;">{{ __('emails.login_alert_time') }}</td>
            <td style="padding:4px 0; color:#2A1015;">{{ $time->format('M j, Y H:i') }}</td>
        </tr>
        <tr>
            <td style="padding:4px 0; color:#9C5064;">{{ __('emails.login_alert_ip') }}</td>
            <td style="padding:4px 0; color:#2A1015;">{{ $ip }}</td>
        </tr>
        <tr>
            <td style="padding:4px 0; color:#9C5064;">{{ __('emails.login_alert_device') }}</td>
            <td style="padding:4px 0; color:#2A1015;">{{ $device }}</td>
        </tr>
        <tr>
            <td style="padding:4px 0; color:#9C5064;">{{ __('emails.login_alert_browser') }}</td>
            <td style="padding:4px 0; color:#2A1015;">{{ $browser }}</td>
        </tr>
    </table>

    <p style="font-size:13px; color:#5a4448; font-family:sans-serif;">
        {{ __('emails.login_alert_ok_note') }}
    </p>

    @include('emails.partials.button', ['href' => route('password.request'), 'label' => __('emails.login_alert_reset_button')])
@endsection
