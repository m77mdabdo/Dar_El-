@extends('admin.layout')

@section('title', 'Edit Coupon')

@section('content')
    <form method="POST" action="{{ route('admin.coupons.update', $coupon) }}" class="space-y-4 max-w-xl">
        @method('PUT')
        @include('admin.coupons._form')
    </form>
@endsection
