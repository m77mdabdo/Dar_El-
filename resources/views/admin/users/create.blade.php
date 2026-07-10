@extends('admin.layout')

@section('title', __('users.add_user'))

@section('content')
    <div class="dj-admin-card p-4 sm:p-6 max-w-3xl">
        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-4">
            @include('admin.users._form')
        </form>
    </div>
@endsection
