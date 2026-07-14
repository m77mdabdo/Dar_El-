@extends('layouts.storefront')

@section('title', __('Saved Addresses') . ' — Dar El Jamila')

@section('content')
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="flex items-center justify-between mb-8">
            <h1 class="font-serif text-3xl">{{ __('Saved Addresses') }}</h1>
            <a href="{{ route('account.addresses.create') }}" class="bg-rose-700 hover:bg-rose-800 text-white text-sm px-4 py-2 rounded">{{ __('Add Address') }}</a>
        </div>

        <div class="space-y-4">
            @forelse ($addresses as $address)
                <div class="bg-white border border-stone-200 rounded-lg p-4 flex justify-between items-start">
                    <div class="text-sm">
                        @if ($address->label)
                            <p class="font-medium">{{ $address->label }} @if($address->is_default) <span class="text-xs text-rose-700">({{ __('Default') }})</span> @endif</p>
                        @endif
                        <p>{{ $address->first_name }} {{ $address->last_name }} &middot; {{ $address->phone }}</p>
                        <p class="text-stone-500">{{ $address->address }}, {{ $address->city }}, {{ $address->governorate }}</p>
                    </div>
                    <div class="flex gap-3 text-sm shrink-0">
                        <a href="{{ route('account.addresses.edit', $address) }}" class="text-rose-700 underline">{{ __('Edit') }}</a>
                        <form method="POST" action="{{ route('account.addresses.destroy', $address) }}" onsubmit="return confirm('{{ __('Remove this address?') }}')">
                            @csrf
                            @method('DELETE')
                            <button class="text-stone-500 underline">{{ __('Delete') }}</button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="text-stone-500 text-sm">{{ __('No saved addresses yet.') }}</p>
            @endforelse
        </div>
    </div>
@endsection
