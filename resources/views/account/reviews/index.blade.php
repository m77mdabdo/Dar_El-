@extends('layouts.storefront')

@section('title', __('reviews.title') . ' — Dar El-Jamila')

@section('content')
    <div class="max-w-3xl mx-auto px-4 sm:px-6 py-12">
        <h1 style="font-size:28px; color:var(--dj-maroon); margin-bottom:30px;">{{ __('reviews.title') }}</h1>

        @if ($reviews->isEmpty())
            <div class="dj-empty-cart" style="text-align:center;">
                ★<br>{{ __('reviews.no_reviews') }}
                <br><a href="{{ route('shop.index') }}" style="color:var(--dj-maroon); text-decoration:underline;">{{ __('Continue shopping') }}</a>
            </div>
        @else
            @foreach ($reviews as $review)
                @php
                    $djReviewStatusColor = match ($review->status) {
                        'approved' => '#237a3f',
                        'rejected' => '#b42318',
                        default => '#8a5a2a',
                    };
                    $djReviewStatusBg = match ($review->status) {
                        'approved' => '#e3f3e6',
                        'rejected' => '#fbe4e4',
                        default => 'rgba(232,195,154,.35)',
                    };
                @endphp
                <div class="dj-review-card">
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; flex-wrap:wrap;">
                        <a href="{{ route('shop.show', $review->product) }}" style="font-weight:700; font-size:14px; color:var(--dj-maroon);">{{ trans_field($review->product, 'name') }}</a>
                        <span style="font-size:11px; color:{{ $djReviewStatusColor }}; background:{{ $djReviewStatusBg }}; padding:2px 10px; border-radius:999px;">{{ __('reviews.status_'.$review->status) }}</span>
                    </div>
                    <span class="dj-stars">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</span>
                    <p style="font-size:13.5px; color:#8a6b70;">{{ str($review->comment)->limit(120) }}</p>
                    <p style="font-size:11.5px; color:#a68b8f; margin-top:4px;">{{ $review->created_at->translatedFormat('M j, Y') }}</p>

                    <div style="display:flex; gap:14px; margin-top:8px;">
                        @if ($review->status === 'pending')
                            <a href="{{ route('shop.show', $review->product) }}" style="font-size:12.5px; color:var(--dj-maroon); text-decoration:underline;">{{ __('general.edit') }}</a>
                        @endif
                        <form method="POST" action="{{ route('reviews.destroy', $review) }}" onsubmit="return confirm('{{ __('reviews.confirm_delete') }}')">
                            @csrf @method('DELETE')
                            <button type="submit" style="font-size:12.5px; color:#b42318; text-decoration:underline;">{{ __('reviews.delete_review') }}</button>
                        </form>
                    </div>
                </div>
            @endforeach

            <div class="mt-6">{{ $reviews->links() }}</div>
        @endif
    </div>
@endsection
