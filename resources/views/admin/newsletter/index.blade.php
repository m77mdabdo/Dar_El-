@extends('admin.layout')

@section('title', 'Newsletter Subscribers')

@section('content')
    <div class="flex justify-end mb-4">
        <a href="{{ route('admin.newsletter.export') }}" class="bg-stone-800 text-white text-sm px-4 py-2 rounded">Export CSV</a>
    </div>

    <div class="bg-white border border-stone-200 rounded-lg overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-stone-50 text-left text-xs uppercase text-stone-500">
                <tr>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Subscribed At</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @foreach ($subscribers as $subscriber)
                    <tr>
                        <td class="px-4 py-3">{{ $subscriber->email }}</td>
                        <td class="px-4 py-3">{{ $subscriber->created_at->format('M j, Y H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $subscribers->links() }}</div>
@endsection
