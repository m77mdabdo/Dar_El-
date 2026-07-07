@extends('admin.layout')

@section('title', 'Add Product')

@section('content')
    <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data" class="space-y-4 max-w-2xl">
        @include('admin.products._form')
    </form>
@endsection
