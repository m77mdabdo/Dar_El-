@extends('emails.layouts.master')

@php
    $icon = 'envelope';
    $headerTagline = __('emails.admin_tagline');
@endphp

@section('content')
    <p style="font-size:14px; line-height:1.8; color:#5a4448; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif; text-align:center; margin:0 0 26px;">
        {{ __('emails.admin_new_contact_message_intro') }}
    </p>

    @include('emails.partials.info-card', [
        'djIcon' => 'user',
        'djTitle' => __('emails.admin_message_details_title'),
        'djRows' => array_filter([
            ['label' => __('emails.admin_new_contact_message_name'), 'value' => $contactMessage->name],
            ['label' => __('emails.admin_new_contact_message_email'), 'value' => $contactMessage->email],
            $contactMessage->subject ? ['label' => __('emails.admin_new_contact_message_subject_label'), 'value' => $contactMessage->subject] : null,
        ]),
    ])

    <div style="background:#F7EFE4; border-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}:3px solid #601526; border-radius:10px; padding:14px 18px; margin:0 0 26px;">
        <div style="font-size:11px; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:#601526; margin:0 0 6px; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif;">{{ __('emails.admin_message_body_title') }}</div>
        <p style="font-size:13.5px; line-height:1.7; color:#5a4448; margin:0; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif;">
            {{ $contactMessage->message }}
        </p>
    </div>

    @include('emails.partials.button', ['href' => route('admin.contact-messages.index'), 'label' => __('emails.admin_new_contact_message_button')])
@endsection
