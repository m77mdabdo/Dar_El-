@extends('emails.layouts.master')

@php
    $icon = 'shield';
    $securityNote = __('emails.otp_security_note');
@endphp

@section('content')
    <h2 style="font-size:18px; color:#601526; margin-top:0; font-family:sans-serif;">
        {{ __('emails.otp_greeting', ['name' => $user->name]) }}
    </h2>

    <p style="font-size:14px; line-height:1.8; color:#5a4448; font-family:sans-serif;">
        {{ __('emails.otp_intro') }}
    </p>

    <div style="text-align:center; margin:28px 0;">
        <div style="display:inline-block; background:#F7EFE4; border:1px solid #EFE2CE; border-radius:12px; padding:18px 36px;">
            <span style="font-size:36px; font-weight:700; letter-spacing:10px; color:#601526; font-family:sans-serif;">{{ $otp }}</span>
        </div>
    </div>

    <p style="font-size:13px; color:#9C5064; text-align:center; font-family:sans-serif;">
        {{ __('emails.otp_expires', ['minutes' => $expiresInMinutes]) }}
    </p>
@endsection
