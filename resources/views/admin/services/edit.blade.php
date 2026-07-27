@extends('admin.layout')

@section('title', __('admin_services.edit_service'))

@section('content')
    <div class="dj-admin-card p-4 sm:p-6 max-w-xl">
        <form method="POST" action="{{ route('admin.services.update', $service) }}" class="space-y-4">
            @method('PUT')
            @include('admin.services._form')
        </form>
    </div>
@endsection
