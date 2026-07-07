@extends('admin.layout')

@section('title', 'Add Coupon')

@section('content')
    <form method="POST" action="{{ route('admin.coupons.store') }}" class="space-y-4 max-w-xl">
        @include('admin.coupons._form')
    </form>
@endsection
