@extends('admin.layout')

@section('title', 'Edit Category')

@section('content')
    <form method="POST" action="{{ route('admin.categories.update', $category) }}" enctype="multipart/form-data" class="space-y-4 max-w-xl">
        @method('PUT')
        @include('admin.categories._form')
    </form>
@endsection
