@extends('layouts.storefront')

@section('title', __('Add Address') . ' — Dar El Jamila')

@section('content')
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h1 class="font-serif text-3xl mb-8">{{ __('Add Address') }}</h1>
        <form method="POST" action="{{ route('account.addresses.store') }}" class="space-y-4">
            @include('account.addresses._form')
        </form>
    </div>
@endsection
