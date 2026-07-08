@extends('admin.layout')

@section('title', __('admin.notifications.title'))

@section('content')
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-[var(--dj-maroon-dark)]">{{ __('admin.notifications.title') }}</h2>
        @if ($notifications->contains(fn ($n) => is_null($n->read_at)))
            <button
                type="button"
                onclick="adminMarkAllNotificationsRead('{{ route('admin.notifications.read-all') }}')"
                class="dj-admin-link"
            >
                {{ __('admin.notifications.mark_all_read') }}
            </button>
        @endif
    </div>

    <div class="dj-admin-card overflow-hidden">
        @forelse ($notifications as $notification)
            @include('admin.partials.notification-item', ['notification' => $notification])
        @empty
            <div class="dj-admin-empty">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
                <p class="text-sm">{{ __('admin.notifications.empty') }}</p>
                <p class="text-xs mt-1 opacity-70">{{ __('admin.notifications.empty_hint') }}</p>
            </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $notifications->links() }}</div>
@endsection
