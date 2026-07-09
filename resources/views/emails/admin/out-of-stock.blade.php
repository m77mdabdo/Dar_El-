@extends('emails.layouts.master')

@php
    $icon = 'warning-triangle';
    $headerTagline = __('emails.admin_tagline');
@endphp

@section('content')
    <p style="font-size:14px; line-height:1.8; color:#5a4448; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif; text-align:center; margin:0 0 26px;">
        {{ __('emails.admin_out_of_stock_intro', ['product' => $product->name_en, 'size' => $size->size]) }}
    </p>

    @include('emails.partials.button', ['href' => route('admin.products.edit', $product), 'label' => __('emails.admin_out_of_stock_button')])
@endsection
