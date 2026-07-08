@extends('admin.layout')

@section('title', __('messages.title'))

@section('content')
    <div class="space-y-3">
        @forelse ($messages as $message)
            <div class="dj-admin-card p-4 {{ $message->is_read ? '' : 'ring-1 ring-[var(--dj-rose-dust)]' }}">
                <div class="flex justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-semibold text-[var(--dj-ink)] truncate">{{ $message->name }} <span class="text-[var(--dj-rose-dust)] font-normal text-sm">({{ $message->email }})</span></p>
                        @if ($message->subject)
                            <p class="text-sm text-[var(--dj-maroon)]">{{ $message->subject }}</p>
                        @endif
                    </div>
                    <div class="text-end text-xs text-[var(--dj-rose-dust)] shrink-0">
                        {{ $message->created_at->format('M j, Y H:i') }}
                        @unless ($message->is_read)
                            <form method="POST" action="{{ route('admin.contact-messages.read', $message) }}">
                                @csrf @method('PATCH')
                                <button class="block mt-1 dj-admin-link ms-auto">{{ __('general.mark_as_read') }}</button>
                            </form>
                        @endunless
                    </div>
                </div>
                <p class="text-sm text-[var(--dj-ink)] mt-2">{{ $message->message }}</p>
            </div>
        @empty
            <div class="dj-admin-card dj-admin-table-empty">{{ __('messages.no_messages') }}</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $messages->links() }}</div>
@endsection
