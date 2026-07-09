@extends('emails.layouts.master')

@php
    $icon = 'user';
    $headerTagline = __('emails.admin_tagline');
@endphp

@section('content')
    <p style="font-size:14px; line-height:1.8; color:#5a4448; font-family:sans-serif;">
        {{ __('emails.admin_new_customer_intro', ['name' => $customer->name]) }}
    </p>

    <table style="width:100%; border-collapse:collapse; margin:16px 0; font-size:13.5px; font-family:sans-serif;">
        <tr>
            <td style="padding:4px 0; color:#9C5064; width:40%;">{{ __('emails.admin_new_customer_email') }}</td>
            <td style="padding:4px 0; color:#2A1015;">{{ $customer->email }}</td>
        </tr>
        @if ($customer->phone)
            <tr>
                <td style="padding:4px 0; color:#9C5064;">{{ __('emails.admin_new_customer_phone') }}</td>
                <td style="padding:4px 0; color:#2A1015;">{{ $customer->phone }}</td>
            </tr>
        @endif
    </table>

    @include('emails.partials.button', ['href' => route('admin.customers.show', $customer), 'label' => __('emails.admin_new_customer_button')])
@endsection
