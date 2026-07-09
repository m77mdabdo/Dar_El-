@extends('emails.layouts.master')

@php
    $icon = 'user';
    $headerTagline = __('emails.admin_tagline');
@endphp

@section('content')
    <p style="font-size:14px; line-height:1.8; color:#5a4448; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif; text-align:center; margin:0 0 26px;">
        {{ __('emails.admin_new_customer_intro', ['name' => $customer->name]) }}
    </p>

    @include('emails.partials.info-card', [
        'djIcon' => 'user',
        'djTitle' => __('emails.admin_customer_details_title'),
        'djRows' => array_filter([
            ['label' => __('emails.admin_new_customer_email'), 'value' => $customer->email],
            $customer->phone ? ['label' => __('emails.admin_new_customer_phone'), 'value' => $customer->phone] : null,
        ]),
    ])

    @include('emails.partials.button', ['href' => route('admin.customers.show', $customer), 'label' => __('emails.admin_new_customer_button')])
@endsection
