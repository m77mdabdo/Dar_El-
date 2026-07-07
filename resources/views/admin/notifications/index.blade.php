@extends('admin.layout')

@section('title', __('admin.notifications.title'))

@section('content')
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold">{{ __('admin.notifications.title') }}</h2>
        @if ($notifications->contains(fn ($n) => is_null($n->read_at)))
            <button
                type="button"
                onclick="adminMarkAllNotificationsRead('{{ route('admin.notifications.read-all') }}')"
                class="text-sm text-rose-700 hover:underline"
            >
                {{ __('admin.notifications.mark_all_read') }}
            </button>
        @endif
    </div>

    <div class="bg-white border border-stone-200 rounded-lg overflow-hidden">
        @forelse ($notifications as $notification)
            @include('admin.partials.notification-item', ['notification' => $notification])
        @empty
            <div class="px-6 py-16 text-center">
                <p class="text-stone-500 text-sm">{{ __('admin.notifications.empty') }}</p>
                <p class="text-stone-400 text-xs mt-1">{{ __('admin.notifications.empty_hint') }}</p>
            </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $notifications->links() }}</div>
@endsection
