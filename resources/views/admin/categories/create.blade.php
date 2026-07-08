@extends('admin.layout')

@section('title', __('categories.add_category'))

@section('content')
    <div class="dj-admin-card p-4 sm:p-6 max-w-xl">
        <form method="POST" action="{{ route('admin.categories.store') }}" enctype="multipart/form-data" class="space-y-4">
            @include('admin.categories._form')
        </form>
    </div>
@endsection
