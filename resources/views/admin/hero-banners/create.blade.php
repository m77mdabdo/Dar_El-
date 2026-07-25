@extends('admin.layout')

@section('title', __('hero_banners.add_hero_banner'))

@section('content')
    <div class="dj-admin-card p-4 sm:p-6 max-w-xl">
        <form method="POST" action="{{ route('admin.hero-banners.store') }}" enctype="multipart/form-data" class="space-y-4">
            @include('admin.hero-banners._form')
        </form>
    </div>
@endsection
