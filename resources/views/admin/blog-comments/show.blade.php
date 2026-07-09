@extends('admin.layout')

@section('title', __('blog_comments.title'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.blog-comments.index') }}" class="dj-admin-link">&larr; {{ __('general.back') }}</a>
    </div>

    <div class="max-w-3xl">
        @php
            $djCommentBadge = match ($comment->status) {
                'approved' => 'dj-admin-badge-success',
                'rejected' => 'dj-admin-badge-danger',
                default => 'dj-admin-badge-gold',
            };
        @endphp

        <div class="dj-admin-card p-4 sm:p-6 space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <p class="font-semibold text-[var(--dj-ink)]">{{ $comment->name }}</p>
                    @if ($comment->user)
                        <p class="text-xs text-[var(--dj-rose-dust)]">{{ $comment->user->email }}</p>
                    @endif
                </div>
                <span class="dj-admin-badge {{ $djCommentBadge }}">{{ __('blog_comments.status_'.$comment->status) }}</span>
            </div>

            <div>
                <p class="text-xs text-[var(--dj-rose-dust)] mb-1">{{ __('blog_comments.blog_post') }}</p>
                <a href="{{ route('admin.blog.edit', $comment->blogPost) }}" class="dj-admin-link font-medium">{{ $comment->blogPost->title_en }}</a>
            </div>

            <div>
                <p class="text-xs text-[var(--dj-rose-dust)] mb-1">{{ __('blog_comments.comment') }}</p>
                <p class="text-sm text-[var(--dj-ink)]">{{ $comment->comment }}</p>
            </div>

            @if ($comment->ip_address)
                <div>
                    <p class="text-xs text-[var(--dj-rose-dust)] mb-1">{{ __('blog_comments.ip_address') }}</p>
                    <p class="text-sm text-[var(--dj-ink)]">{{ $comment->ip_address }}</p>
                </div>
            @endif

            @if ($comment->user_agent)
                <div>
                    <p class="text-xs text-[var(--dj-rose-dust)] mb-1">{{ __('blog_comments.user_agent') }}</p>
                    <p class="text-xs text-[var(--dj-rose-dust)] break-all">{{ $comment->user_agent }}</p>
                </div>
            @endif

            @if ($comment->status === 'approved' && $comment->approvedBy)
                <p class="text-xs text-[var(--dj-rose-dust)]">{{ $comment->approved_at->format('M j, Y H:i') }} &middot; {{ $comment->approvedBy->name }}</p>
            @endif

            @if ($comment->status === 'rejected')
                <div class="border-t border-[var(--dj-cream-2)] pt-3">
                    @if ($comment->rejectedBy)
                        <p class="text-xs text-[var(--dj-rose-dust)] mb-1">{{ $comment->rejected_at->format('M j, Y H:i') }} &middot; {{ $comment->rejectedBy->name }}</p>
                    @endif
                    @if ($comment->rejection_reason)
                        <p class="text-xs text-[var(--dj-rose-dust)] mb-1">{{ __('blog_comments.rejection_reason_label') }}</p>
                        <p class="text-sm text-[var(--dj-ink)]">{{ $comment->rejection_reason }}</p>
                    @endif
                </div>
            @endif
        </div>

        <div class="dj-admin-card p-4 sm:p-6 mt-4 flex flex-wrap gap-3">
            @unless ($comment->status === 'approved')
                <form method="POST" action="{{ route('admin.blog-comments.approve', $comment) }}">
                    @csrf @method('PATCH')
                    <button class="dj-admin-btn dj-admin-btn-primary">{{ __('reviews.approve') }}</button>
                </form>
            @endunless

            @unless ($comment->status === 'rejected')
                <details>
                    <summary class="dj-admin-btn dj-admin-btn-secondary inline-flex cursor-pointer list-none">{{ __('reviews.reject') }}</summary>
                    <form method="POST" action="{{ route('admin.blog-comments.reject', $comment) }}" class="mt-3 flex flex-col sm:flex-row gap-2">
                        @csrf @method('PATCH')
                        <input type="text" name="reason" placeholder="{{ __('blog_comments.reject_reason_placeholder') }}" class="dj-admin-input flex-1">
                        <button class="dj-admin-btn dj-admin-btn-secondary shrink-0">{{ __('reviews.reject') }}</button>
                    </form>
                </details>
            @endunless

            <form method="POST" action="{{ route('admin.blog-comments.destroy', $comment) }}" onsubmit="return confirm('{{ __('blog_comments.confirm_delete') }}')">
                @csrf @method('DELETE')
                <button class="dj-admin-btn dj-admin-btn-danger">{{ __('general.delete') }}</button>
            </form>
        </div>
    </div>
@endsection
