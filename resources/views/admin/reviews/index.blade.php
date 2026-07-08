@extends('admin.layout')

@section('title', __('reviews.title'))

@section('content')
    <div class="dj-admin-card dj-admin-table-wrap">
        <table class="dj-admin-table">
            <thead>
                <tr>
                    <th>{{ __('reviews.product') }}</th>
                    <th>{{ __('general.name') }}</th>
                    <th>{{ __('reviews.rating') }}</th>
                    <th>{{ __('reviews.comment') }}</th>
                    <th>{{ __('general.status') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reviews as $review)
                    <tr>
                        <td class="font-medium text-[var(--dj-ink)]">{{ $review->product->name_en }}</td>
                        <td>{{ $review->name }}</td>
                        <td class="text-[var(--dj-gold-bright)]">{{ str_repeat('★', $review->rating) }}<span class="text-[var(--dj-cream-2)]">{{ str_repeat('★', 5 - $review->rating) }}</span></td>
                        <td>{{ str($review->comment)->limit(60) }}</td>
                        <td><span class="dj-admin-badge {{ $review->is_approved ? 'dj-admin-badge-success' : 'dj-admin-badge-gold' }}">{{ $review->is_approved ? __('general.approved') : __('general.pending') }}</span></td>
                        <td class="text-end space-x-3 rtl:space-x-reverse">
                            @unless ($review->is_approved)
                                <form method="POST" action="{{ route('admin.reviews.approve', $review) }}" class="inline">
                                    @csrf @method('PATCH')
                                    <button class="dj-admin-link" style="color:#237a3f;">{{ __('reviews.approve') }}</button>
                                </form>
                            @endunless
                            @if ($review->is_approved)
                                <form method="POST" action="{{ route('admin.reviews.reject', $review) }}" class="inline">
                                    @csrf @method('PATCH')
                                    <button class="dj-admin-link-muted">{{ __('reviews.reject') }}</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="dj-admin-table-empty">{{ __('reviews.no_reviews') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $reviews->links() }}</div>
@endsection
