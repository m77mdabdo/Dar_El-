@extends('admin.layout')

@section('title', __('coupons.edit_coupon'))

@section('content')
    <div class="dj-admin-card p-4 sm:p-6 max-w-xl">
        <form method="POST" action="{{ route('admin.coupons.update', $coupon) }}" class="space-y-4">
            @method('PUT')
            @include('admin.coupons._form')
        </form>
    </div>
@endsection
