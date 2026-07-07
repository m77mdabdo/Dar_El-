@extends('admin.layout')

@section('title', 'Reviews')

@section('content')
    <div class="bg-white border border-stone-200 rounded-lg overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-stone-50 text-left text-xs uppercase text-stone-500">
                <tr>
                    <th class="px-4 py-3">Product</th>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Rating</th>
                    <th class="px-4 py-3">Comment</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @foreach ($reviews as $review)
                    <tr>
                        <td class="px-4 py-3">{{ $review->product->name_en }}</td>
                        <td class="px-4 py-3">{{ $review->name }}</td>
                        <td class="px-4 py-3">{{ $review->rating }}/5</td>
                        <td class="px-4 py-3">{{ str($review->comment)->limit(60) }}</td>
                        <td class="px-4 py-3">{{ $review->is_approved ? 'Approved' : 'Pending' }}</td>
                        <td class="px-4 py-3 text-right space-x-2">
                            @unless ($review->is_approved)
                                <form method="POST" action="{{ route('admin.reviews.approve', $review) }}" class="inline">
                                    @csrf @method('PATCH')
                                    <button class="text-green-700 underline">Approve</button>
                                </form>
                            @endunless
                            @if ($review->is_approved)
                                <form method="POST" action="{{ route('admin.reviews.reject', $review) }}" class="inline">
                                    @csrf @method('PATCH')
                                    <button class="text-red-600 underline">Reject</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $reviews->links() }}</div>
@endsection
