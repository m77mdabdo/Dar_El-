@extends('layouts.storefront')

@section('title', $order->order_number . ' — Dar El-Jamila')

@section('content')
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <a href="{{ route('account.orders.index') }}" class="text-sm text-rose-700 underline">&larr; {{ __('My Orders') }}</a>

        <div class="flex flex-wrap items-center justify-between gap-3 mt-4 mb-8">
            <div>
                <h1 class="font-serif text-3xl">{{ $order->order_number }}</h1>
                <p class="text-sm text-stone-500 mt-1">{{ $order->created_at->translatedFormat('F j, Y') }} &middot; <span class="font-medium text-stone-700">{{ __('orders.status_'.$order->status) }}</span></p>
            </div>
            <a href="{{ route('account.orders.invoice', $order) }}" class="bg-stone-800 text-white text-sm px-4 py-2 rounded">{{ __('orders.download_invoice') }}</a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="md:col-span-2 space-y-6">
                <div class="bg-white border border-stone-200 rounded-lg divide-y divide-stone-200">
                    @foreach ($order->items as $item)
                        <div class="p-4 flex items-center gap-4 text-sm">
                            @if ($item->product?->cover_image_src)
                                <img src="{{ $item->product->cover_image_src }}" alt="" class="w-14 h-14 rounded-lg object-cover border border-stone-200 shrink-0">
                            @endif
                            <div class="flex-1 min-w-0">
                                <p class="font-medium truncate">{{ $item->product ? trans_field($item->product, 'name') : $item->product_name }}</p>
                                <p class="text-stone-500">
                                    @if ($item->size){{ __('orders.item_size', ['size' => $item->size]) }} &middot; @endif
                                    {{ __('orders.item_qty', ['qty' => $item->quantity]) }}
                                </p>
                            </div>
                            <span class="font-medium shrink-0">{{ number_format($item->price * $item->quantity) }} EGP</span>
                        </div>
                    @endforeach
                </div>

                <div>
                    <h2 class="font-medium mb-3">{{ __('Status History') }}</h2>
                    <ul class="space-y-2 text-sm">
                        @foreach ($order->statusHistories as $history)
                            <li class="flex justify-between border-b border-stone-100 pb-2">
                                <span>{{ ucfirst($history->status) }} @if($history->note) — {{ $history->note }} @endif</span>
                                <span class="text-stone-400">{{ $history->created_at->translatedFormat('M j, Y H:i') }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <div class="space-y-4 text-sm">
                <div class="bg-white border border-stone-200 rounded-lg p-4">
                    <h2 class="font-medium mb-2">{{ __('Shipping Address') }}</h2>
                    <p>{{ $order->address }}</p>
                    <p>{{ $order->city }}, {{ $order->governorate }}</p>
                    <p class="mt-2 text-stone-500">{{ $order->customer_phone }}</p>
                </div>
                <div class="bg-white border border-stone-200 rounded-lg p-4">
                    <h2 class="font-medium mb-2">{{ __('orders.payment') }}</h2>
                    <p>{{ $order->payment_method === \App\Models\Order::PAYMENT_METHOD_COD ? __('emails.order_payment_method_cod') : $order->payment_method }}</p>
                </div>
                <div class="bg-white border border-stone-200 rounded-lg p-4 space-y-1">
                    <div class="flex justify-between"><span>{{ __('Subtotal') }}</span><span>{{ number_format($order->subtotal) }} EGP</span></div>
                    <div class="flex justify-between"><span>{{ __('Shipping') }}</span><span>{{ number_format($order->shipping_fee) }} EGP</span></div>
                    @if ($order->discount_amount > 0)
                        <div class="flex justify-between text-green-700"><span>{{ __('Discount') }}</span><span>-{{ number_format($order->discount_amount) }} EGP</span></div>
                    @endif
                    <div class="flex justify-between font-semibold pt-2 border-t border-stone-200"><span>{{ __('Total') }}</span><span>{{ number_format($order->total) }} EGP</span></div>
                </div>
            </div>
        </div>
    </div>
@endsection
