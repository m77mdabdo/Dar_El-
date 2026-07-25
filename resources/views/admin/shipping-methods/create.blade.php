@extends('admin.layout')

@section('title', __('shipping_methods.add_shipping_method'))

@section('content')
    <div class="dj-admin-card p-4 sm:p-6 max-w-xl">
        <form method="POST" action="{{ route('admin.shipping-methods.store') }}">
            @include('admin.shipping-methods._form')
        </form>
    </div>
@endsection
