@extends('layouts.storefront')

@section('title', __('Dashboard') . ' — Dar El Jamila')

@section('content')
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h1 class="font-serif text-3xl mb-2">{{ __('Welcome back, :name', ['name' => Auth::user()->name]) }}</h1>
        <p class="text-stone-600 mb-8">{{ __("You're logged in.") }}</p>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <a href="{{ route('shop.index') }}" class="bg-white border border-stone-200 rounded-lg p-6 text-center hover:shadow-md transition">
                <span class="font-medium">{{ __('Shop the Collection') }}</span>
            </a>
            <a href="{{ route('account.orders.index') }}" class="bg-white border border-stone-200 rounded-lg p-6 text-center hover:shadow-md transition">
                <span class="font-medium">{{ __('My Orders') }}</span>
            </a>
            <a href="{{ route('profile.edit') }}" class="bg-white border border-stone-200 rounded-lg p-6 text-center hover:shadow-md transition">
                <span class="font-medium">{{ __('Profile') }}</span>
            </a>
        </div>
    </div>
@endsection
