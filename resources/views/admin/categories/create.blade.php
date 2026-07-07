@extends('admin.layout')

@section('title', 'Add Category')

@section('content')
    <form method="POST" action="{{ route('admin.categories.store') }}" enctype="multipart/form-data" class="space-y-4 max-w-xl">
        @include('admin.categories._form')
    </form>
@endsection
