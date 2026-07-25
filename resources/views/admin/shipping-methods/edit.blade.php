@extends('admin.layout')

@section('title', __('shipping_methods.edit_shipping_method'))

@section('content')
    <div class="dj-admin-card p-4 sm:p-6 max-w-xl">
        <form method="POST" action="{{ route('admin.shipping-methods.update', $shippingMethod) }}">
            @method('PUT')
            @include('admin.shipping-methods._form')
        </form>
    </div>
@endsection
