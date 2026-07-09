@extends('emails.layouts.master')

@php
    $icon = 'bag';
    $djIsRtl = app()->getLocale() === 'ar';
    $headerTagline = __('emails.admin_tagline');
@endphp

@section('content')
    <p style="font-size:14px; line-height:1.8; color:#5a4448; font-family:sans-serif;">
        {{ __('emails.admin_new_order_intro') }}
    </p>

    <table style="width:100%; border-collapse:collapse; margin:16px 0; font-size:13.5px; font-family:sans-serif;">
        <tr>
            <td style="padding:4px 0; color:#9C5064; width:40%;">{{ __('orders.order_number') }}</td>
            <td style="padding:4px 0; color:#2A1015; font-weight:600;">{{ $order->order_number }}</td>
        </tr>
        <tr>
            <td style="padding:4px 0; color:#9C5064;">{{ __('emails.admin_new_order_customer') }}</td>
            <td style="padding:4px 0; color:#2A1015;">{{ $order->customer_name }}</td>
        </tr>
        <tr>
            <td style="padding:4px 0; color:#9C5064;">{{ __('emails.admin_new_order_total') }}</td>
            <td style="padding:4px 0; color:#601526; font-weight:700;">{{ number_format($order->total) }} EGP</td>
        </tr>
    </table>

    @include('emails.partials.button', ['href' => route('admin.orders.show', $order), 'label' => __('emails.admin_new_order_button')])
@endsection
