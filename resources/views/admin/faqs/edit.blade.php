@extends('admin.layout')

@section('title', __('faqs.edit_faq'))

@section('content')
    <div class="dj-admin-card p-4 sm:p-6 max-w-xl">
        <form method="POST" action="{{ route('admin.faqs.update', $faq) }}" class="space-y-4">
            @method('PUT')
            @include('admin.faqs._form')
        </form>
    </div>
@endsection
