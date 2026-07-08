@extends('admin.layout')

@section('title', __('products.add_product'))

@section('content')
    <div class="dj-admin-card p-4 sm:p-6 max-w-2xl">
        <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data" class="space-y-4">
            @include('admin.products._form')
        </form>
    </div>
@endsection
