@extends('admin.layout')

@section('title', 'Contact Messages')

@section('content')
    <div class="space-y-3">
        @foreach ($messages as $message)
            <div class="bg-white border border-stone-200 rounded-lg p-4 {{ $message->is_read ? '' : 'ring-1 ring-rose-200' }}">
                <div class="flex justify-between">
                    <div>
                        <p class="font-medium">{{ $message->name }} <span class="text-stone-400 font-normal text-sm">({{ $message->email }})</span></p>
                        @if ($message->subject)
                            <p class="text-sm text-stone-600">{{ $message->subject }}</p>
                        @endif
                    </div>
                    <div class="text-right text-xs text-stone-400">
                        {{ $message->created_at->format('M j, Y H:i') }}
                        @unless ($message->is_read)
                            <form method="POST" action="{{ route('admin.contact-messages.read', $message) }}">
                                @csrf @method('PATCH')
                                <button class="block mt-1 text-rose-700 underline">Mark as read</button>
                            </form>
                        @endunless
                    </div>
                </div>
                <p class="text-sm text-stone-700 mt-2">{{ $message->message }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-4">{{ $messages->links() }}</div>
@endsection
