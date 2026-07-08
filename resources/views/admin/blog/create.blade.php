@extends('admin.layout')

@section('title', __('blog.add_post'))

@section('content')
    <div class="dj-admin-card p-4 sm:p-6 max-w-2xl">
        <form method="POST" action="{{ route('admin.blog.store') }}" enctype="multipart/form-data" class="space-y-4">
            @include('admin.blog._form')
        </form>
    </div>
@endsection
