@extends('emails.layouts.master')

@php
    $icon = 'envelope';
    $headerTagline = __('emails.admin_tagline');
@endphp

@section('content')
    <p style="font-size:14px; line-height:1.8; color:#5a4448; font-family:sans-serif;">
        {{ __('emails.admin_new_contact_message_intro') }}
    </p>

    <table style="width:100%; border-collapse:collapse; margin:16px 0; font-size:13.5px; font-family:sans-serif;">
        <tr>
            <td style="padding:4px 0; color:#9C5064; width:40%;">{{ __('emails.admin_new_contact_message_name') }}</td>
            <td style="padding:4px 0; color:#2A1015;">{{ $contactMessage->name }}</td>
        </tr>
        <tr>
            <td style="padding:4px 0; color:#9C5064;">{{ __('emails.admin_new_contact_message_email') }}</td>
            <td style="padding:4px 0; color:#2A1015;">{{ $contactMessage->email }}</td>
        </tr>
        @if ($contactMessage->subject)
            <tr>
                <td style="padding:4px 0; color:#9C5064; vertical-align:top;">{{ __('emails.admin_new_contact_message_subject_label') }}</td>
                <td style="padding:4px 0; color:#2A1015;">{{ $contactMessage->subject }}</td>
            </tr>
        @endif
    </table>

    <p style="font-size:13.5px; line-height:1.7; color:#5a4448; background:#F7EFE4; border-radius:8px; padding:12px 16px; font-family:sans-serif;">
        {{ $contactMessage->message }}
    </p>

    @include('emails.partials.button', ['href' => route('admin.contact-messages.index'), 'label' => __('emails.admin_new_contact_message_button')])
@endsection
