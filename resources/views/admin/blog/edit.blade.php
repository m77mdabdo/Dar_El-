@extends('admin.layout')

@section('title', 'Edit Post')

@section('content')
    <form method="POST" action="{{ route('admin.blog.update', $post) }}" enctype="multipart/form-data" class="space-y-4 max-w-2xl">
        @method('PUT')
        @include('admin.blog._form')
    </form>
@endsection
