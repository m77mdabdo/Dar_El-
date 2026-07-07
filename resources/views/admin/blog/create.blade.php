@extends('admin.layout')

@section('title', 'Add Post')

@section('content')
    <form method="POST" action="{{ route('admin.blog.store') }}" enctype="multipart/form-data" class="space-y-4 max-w-2xl">
        @include('admin.blog._form')
    </form>
@endsection
