@extends('admin.layout')

@section('title', __('reviews.title'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.reviews.index') }}" class="dj-admin-link">&larr; {{ __('general.back') }}</a>
    </div>

    <div class="max-w-3xl">
        @php
            $djReviewBadge = match ($review->status) {
                'approved' => 'dj-admin-badge-success',
                'rejected' => 'dj-admin-badge-danger',
                default => 'dj-admin-badge-gold',
            };
        @endphp

        <div class="dj-admin-card p-4 sm:p-6 space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <p class="font-semibold text-[var(--dj-ink)]">{{ $review->name }}</p>
                    @if ($review->user)
                        <p class="text-xs text-[var(--dj-rose-dust)]">{{ $review->user->email }}</p>
                    @endif
                </div>
                <div class="flex gap-2">
                    <span class="dj-admin-badge {{ $djReviewBadge }}">{{ __('reviews.status_'.$review->status) }}</span>
                    @if ($review->is_verified_purchase)
                        <span class="dj-admin-badge dj-admin-badge-info">{{ __('reviews.verified_purchase') }}</span>
                    @endif
                    @if ($review->is_featured)
                        <span class="dj-admin-badge dj-admin-badge-info">{{ __('reviews.featured') }}</span>
                    @endif
                </div>
            </div>

            <div>
                <p class="text-xs text-[var(--dj-rose-dust)] mb-1">{{ __('reviews.product') }}</p>
                <a href="{{ route('admin.products.edit', $review->product) }}" class="dj-admin-link font-medium">{{ $review->product->name_en }}</a>
            </div>

            <div>
                <p class="text-xs text-[var(--dj-rose-dust)] mb-1">{{ __('reviews.rating') }}</p>
                <span class="text-[var(--dj-gold-bright)] text-lg">{{ str_repeat('★', $review->rating) }}<span class="text-[var(--dj-cream-2)]">{{ str_repeat('★', 5 - $review->rating) }}</span></span>
            </div>

            @if ($review->title)
                <div>
                    <p class="text-xs text-[var(--dj-rose-dust)] mb-1">{{ __('reviews.title_label') }}</p>
                    <p class="font-medium text-[var(--dj-ink)]">{{ $review->title }}</p>
                </div>
            @endif

            <div>
                <p class="text-xs text-[var(--dj-rose-dust)] mb-1">{{ __('reviews.comment') }}</p>
                <p class="text-sm text-[var(--dj-ink)]">{{ $review->comment }}</p>
            </div>

            @if ($review->images->isNotEmpty())
                <div>
                    <p class="text-xs text-[var(--dj-rose-dust)] mb-2">{{ __('reviews.photos_label') }}</p>
                    <div class="flex flex-wrap gap-3">
                        @foreach ($review->images as $image)
                            <img src="{{ asset('storage/'.$image->path) }}" class="w-24 h-24 object-cover rounded-lg border border-[var(--dj-cream-2)]">
                        @endforeach
                    </div>
                </div>
            @endif

            <div>
                <p class="text-xs text-[var(--dj-rose-dust)] mb-1">{{ __('reviews.helpful_count', ['count' => $review->helpful_count]) }}</p>
            </div>

            @if ($review->status === 'approved' && $review->approvedBy)
                <p class="text-xs text-[var(--dj-rose-dust)]">{{ $review->approved_at->format('M j, Y H:i') }} &middot; {{ $review->approvedBy->name }}</p>
            @endif

            @if ($review->status === 'rejected')
                <div class="border-t border-[var(--dj-cream-2)] pt-3">
                    @if ($review->rejectedBy)
                        <p class="text-xs text-[var(--dj-rose-dust)] mb-1">{{ $review->rejected_at->format('M j, Y H:i') }} &middot; {{ $review->rejectedBy->name }}</p>
                    @endif
                    @if ($review->rejection_reason)
                        <p class="text-xs text-[var(--dj-rose-dust)] mb-1">{{ __('reviews.rejection_reason_label') }}</p>
                        <p class="text-sm text-[var(--dj-ink)]">{{ $review->rejection_reason }}</p>
                    @endif
                </div>
            @endif
        </div>

        <div class="dj-admin-card p-4 sm:p-6 mt-4 flex flex-wrap gap-3">
            @unless ($review->status === 'approved')
                <form method="POST" action="{{ route('admin.reviews.approve', $review) }}">
                    @csrf @method('PATCH')
                    <button class="dj-admin-btn dj-admin-btn-primary">{{ __('reviews.approve') }}</button>
                </form>
            @endunless

            @unless ($review->status === 'rejected')
                <details>
                    <summary class="dj-admin-btn dj-admin-btn-secondary inline-flex cursor-pointer list-none">{{ __('reviews.reject') }}</summary>
                    <form method="POST" action="{{ route('admin.reviews.reject', $review) }}" class="mt-3 flex flex-col sm:flex-row gap-2">
                        @csrf @method('PATCH')
                        <input type="text" name="reason" placeholder="{{ __('reviews.reject_reason_placeholder') }}" class="dj-admin-input flex-1">
                        <button class="dj-admin-btn dj-admin-btn-secondary shrink-0">{{ __('reviews.reject') }}</button>
                    </form>
                </details>
            @endunless

            <form method="POST" action="{{ route($review->is_featured ? 'admin.reviews.unfeature' : 'admin.reviews.feature', $review) }}">
                @csrf @method('PATCH')
                <button class="dj-admin-btn dj-admin-btn-secondary">{{ $review->is_featured ? __('reviews.unfeature') : __('reviews.feature') }}</button>
            </form>

            <form method="POST" action="{{ route('admin.reviews.destroy', $review) }}" onsubmit="return confirm('{{ __('reviews.confirm_delete') }}')">
                @csrf @method('DELETE')
                <button class="dj-admin-btn dj-admin-btn-danger">{{ __('general.delete') }}</button>
            </form>
        </div>
    </div>
@endsection
