@extends('emails.layouts.master')

@php
    $icon = 'bag';
    $djIsRtl = app()->getLocale() === 'ar';
    $headerTagline = __('emails.order_confirmation_tagline');
@endphp

@section('content')
    <h2 style="font-size:20px; color:#601526; margin:0 0 4px; font-family: Georgia, 'Times New Roman', serif; text-align:center;">
        {{ __('emails.order_confirmation_greeting', ['name' => $order->customer_name]) }}
    </h2>

    <p style="font-size:14px; line-height:1.8; color:#5a4448; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif; text-align:center; margin:0 0 30px;">
        @if ($invoice)
            {{ __('emails.order_confirmation_intro', ['number' => $order->order_number, 'invoice' => $invoice->invoice_number]) }}
        @else
            {{ __('emails.order_confirmation_intro_no_invoice', ['number' => $order->order_number]) }}
        @endif
    </p>

    @include('emails.partials.product-card', [
        'djRows' => $order->items->map(fn ($item) => [
            'image' => $item->product?->cover_image_src,
            'name' => $djIsRtl ? ($item->product?->name_ar ?: $item->product_name) : ($item->product_name),
            'meta' => array_filter([
                $item->size ? __('emails.order_variant').': '.$item->size : null,
                __('emails.order_qty').': '.$item->quantity,
            ]),
            'price' => number_format($item->price * $item->quantity).' EGP',
        ])->all(),
    ])

    @include('emails.partials.summary-box', [
        'djRows' => array_filter([
            ['label' => __('emails.order_subtotal'), 'value' => number_format($order->subtotal).' EGP'],
            $order->discount_amount > 0 ? ['label' => __('emails.order_discount'), 'value' => '-'.number_format($order->discount_amount).' EGP'] : null,
            ['label' => __('emails.order_shipping_fee'), 'value' => number_format($order->shipping_fee).' EGP'],
        ]),
        'djTotal' => ['label' => __('emails.order_grand_total'), 'value' => number_format($order->total).' EGP'],
    ])

    @include('emails.partials.info-card', [
        'djIcon' => 'document',
        'djTitle' => __('emails.order_details_title'),
        'djRows' => [
            ['label' => __('invoice.order_number'), 'value' => $order->order_number],
            ['label' => __('invoice.date'), 'value' => ($order->created_at ?? now())->translatedFormat('F j, Y')],
            ['label' => __('invoice.order_status'), 'value' => __('orders.status_'.$order->status)],
            ['label' => __('invoice.payment_method'), 'value' => $order->payment_method === 'cod' ? __('emails.order_payment_method_cod') : $order->payment_method],
        ],
    ])

    @include('emails.partials.info-card', [
        'djIcon' => 'location',
        'djTitle' => __('invoice.ship_to'),
        'djRows' => [
            ['label' => __('general.name'), 'value' => $order->customer_name],
            ['label' => __('customers.phone'), 'value' => $order->customer_phone],
            ['label' => __('emails.order_shipping_address'), 'value' => "{$order->address}, {$order->city}, {$order->governorate}"],
        ],
    ])

    @include('emails.partials.button', ['href' => route('account.orders.show', $order), 'label' => __('emails.order_view_button')])

    @if ($invoice?->pdf_path)
        @include('emails.partials.button', [
            'href' => \Illuminate\Support\Facades\URL::temporarySignedRoute('invoice.download', now()->addYear(), ['order' => $order->id]),
            'label' => __('emails.order_download_invoice_button'),
        ])
    @endif
@endsection
