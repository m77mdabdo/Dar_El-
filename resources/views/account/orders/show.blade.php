@extends('layouts.storefront')

@section('title', $order->order_number . ' — Dar El-Jamila')

@section('content')
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <a href="{{ route('account.orders.index') }}" class="text-sm text-rose-700 underline">&larr; {{ __('My Orders') }}</a>

        <div class="flex items-center justify-between mt-4 mb-8">
            <h1 class="font-serif text-3xl">{{ $order->order_number }}</h1>
            <a href="{{ route('account.orders.invoice', $order) }}" class="bg-stone-800 text-white text-sm px-4 py-2 rounded">{{ __('Download Invoice') }}</a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="md:col-span-2 space-y-6">
                <div class="bg-white border border-stone-200 rounded-lg divide-y divide-stone-200">
                    @foreach ($order->items as $item)
                        <div class="p-4 flex justify-between text-sm">
                            <span>{{ $item->product_name }} ({{ $item->size ?? '-' }}) &times; {{ $item->quantity }}</span>
                            <span>{{ number_format($item->price * $item->quantity) }} EGP</span>
                        </div>
                    @endforeach
                </div>

                <div>
                    <h2 class="font-medium mb-3">{{ __('Status History') }}</h2>
                    <ul class="space-y-2 text-sm">
                        @foreach ($order->statusHistories as $history)
                            <li class="flex justify-between border-b border-stone-100 pb-2">
                                <span>{{ ucfirst($history->status) }} @if($history->note) — {{ $history->note }} @endif</span>
                                <span class="text-stone-400">{{ $history->created_at->format('M j, Y H:i') }}</span>
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
