@extends('layouts.storefront')

@section('title', __('My Orders') . ' — Dar El Jamila')

@section('content')
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h1 class="font-serif text-3xl mb-8">{{ __('My Orders') }}</h1>

        <div class="bg-white border border-stone-200 rounded-lg divide-y divide-stone-200">
            @forelse ($orders as $order)
                <div class="p-4 flex items-center justify-between">
                    <div>
                        <a href="{{ route('account.orders.show', $order) }}" class="font-medium hover:text-rose-700">{{ $order->order_number }}</a>
                        <p class="text-sm text-stone-500">{{ $order->created_at->translatedFormat('F j, Y') }} &middot; {{ __('orders.status_'.$order->status) }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-medium">{{ number_format($order->total) }} EGP</p>
                        <div class="flex items-center gap-3 justify-end">
                            <a href="{{ route('account.orders.track', $order) }}" class="text-xs text-rose-700 underline">{{ __('orders.track_title') }}</a>
                            <a href="{{ route('account.orders.invoice', $order) }}" class="text-xs text-rose-700 underline">{{ __('Invoice') }}</a>
                        </div>
                    </div>
                </div>
            @empty
                <p class="p-6 text-stone-500 text-sm">{{ __("You haven't placed any orders yet.") }}</p>
            @endforelse
        </div>

        <div class="mt-6">
            {{ $orders->links() }}
        </div>
    </div>
@endsection
