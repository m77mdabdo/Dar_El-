@extends('layouts.storefront')

@section('title', __('Profile') . ' — Dar El Jamila')

@section('content')
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-6">
        <h1 class="font-serif text-3xl mb-2">{{ __('Profile') }}</h1>

        <div class="bg-white border border-stone-200 rounded-lg p-4 sm:p-8">
            <div class="flex flex-wrap gap-4 text-sm">
                <a href="{{ route('account.orders.index') }}" class="text-rose-700 underline">{{ __('Order History') }}</a>
                <a href="{{ route('account.addresses.index') }}" class="text-rose-700 underline">{{ __('Saved Addresses') }}</a>
            </div>
        </div>

        <div class="bg-white border border-stone-200 rounded-lg p-4 sm:p-8">
            <h2 class="font-serif text-xl mb-4">{{ __('My Orders') }}</h2>

            <div class="divide-y divide-stone-100">
                @forelse ($orders as $order)
                    <div class="py-3 flex items-center justify-between text-sm">
                        <div>
                            <a href="{{ route('account.orders.show', $order) }}" class="font-medium hover:text-rose-700">{{ $order->order_number }}</a>
                            <p class="text-stone-500">{{ $order->created_at->translatedFormat('F j, Y') }} &middot; {{ ucfirst($order->status) }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-medium">{{ number_format($order->total) }} EGP</p>
                            <a href="{{ route('account.orders.invoice', $order) }}" class="text-xs text-rose-700 underline">{{ __('Invoice') }}</a>
                        </div>
                    </div>
                @empty
                    <p class="py-3 text-sm text-stone-500">{{ __("You haven't placed any orders yet.") }}</p>
                @endforelse
            </div>

            @if ($orders->isNotEmpty())
                <div class="mt-4">
                    <a href="{{ route('account.orders.index') }}" class="text-sm text-rose-700 underline">{{ __('View all orders') }}</a>
                </div>
            @endif
        </div>

        <div class="bg-white border border-stone-200 rounded-lg p-4 sm:p-8">
            <div class="max-w-xl">
                @include('profile.partials.update-avatar-form')
            </div>
        </div>

        <div class="bg-white border border-stone-200 rounded-lg p-4 sm:p-8">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="bg-white border border-stone-200 rounded-lg p-4 sm:p-8">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="bg-white border border-stone-200 rounded-lg p-4 sm:p-8">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
@endsection
