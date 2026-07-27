@extends('admin.layout')

@section('title', __('admin_services.add_service'))

@section('content')
    <div class="dj-admin-card p-4 sm:p-6 max-w-xl">
        <form method="POST" action="{{ route('admin.services.store') }}" class="space-y-4">
            @include('admin.services._form')
        </form>
    </div>
@endsection
