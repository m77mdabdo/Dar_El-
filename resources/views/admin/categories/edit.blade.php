@extends('admin.layout')

@section('title', __('categories.edit_category'))

@section('content')
    <div class="dj-admin-card p-4 sm:p-6 max-w-xl">
        <form method="POST" action="{{ route('admin.categories.update', $category) }}" enctype="multipart/form-data" class="space-y-4">
            @method('PUT')
            @include('admin.categories._form')
        </form>
    </div>
@endsection
