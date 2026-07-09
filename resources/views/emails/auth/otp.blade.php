@extends('emails.layouts.master')

@php
    $icon = 'shield';
    $securityNote = __('emails.otp_security_note');
@endphp

@section('content')
    <h2 style="font-size:20px; color:#601526; margin:0 0 4px; font-family: Georgia, 'Times New Roman', serif; text-align:center;">
        {{ __('emails.otp_greeting', ['name' => $user->name]) }}
    </h2>

    <p style="font-size:14px; line-height:1.8; color:#5a4448; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif; text-align:center; margin:0 0 8px;">
        {{ __('emails.otp_intro') }}
    </p>

    <div style="text-align:center; margin:28px 0;">
        <div style="display:inline-block; background:#F7EFE4; border:1px solid #EFE2CE; border-radius:14px; padding:20px 40px;">
            <span style="font-size:38px; font-weight:700; letter-spacing:12px; color:#601526; font-family: Georgia, 'Times New Roman', serif;">{{ $otp }}</span>
        </div>
    </div>

    <p style="font-size:13px; color:#9C5064; text-align:center; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif;">
        {{ __('emails.otp_expires', ['minutes' => $expiresInMinutes]) }}
    </p>
@endsection
