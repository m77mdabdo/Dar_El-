@extends('layouts.storefront')

@section('title', __('blog_comments.title') . ' — Dar El-Jamila')

@section('content')
    <div class="max-w-3xl mx-auto px-4 sm:px-6 py-12">
        <h1 style="font-size:28px; color:var(--dj-maroon); margin-bottom:30px;">{{ __('blog_comments.title') }}</h1>

        @if ($comments->isEmpty())
            <div class="dj-empty-cart" style="text-align:center;">
                💬<br>{{ __('blog_comments.no_comments') }}
                <br><a href="{{ route('blog.index') }}" style="color:var(--dj-maroon); text-decoration:underline;">{{ __('Continue shopping') }}</a>
            </div>
        @else
            @foreach ($comments as $comment)
                @php
                    $djCommentStatusColor = match ($comment->status) {
                        'approved' => '#237a3f', 'rejected' => '#b42318', default => '#8a5a2a',
                    };
                    $djCommentStatusBg = match ($comment->status) {
                        'approved' => '#e3f3e6', 'rejected' => '#fbe4e4', default => 'rgba(232,195,154,.35)',
                    };
                @endphp
                <div class="dj-review-card">
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; flex-wrap:wrap;">
                        <a href="{{ route('blog.show', $comment->blogPost) }}" style="font-weight:700; font-size:14px; color:var(--dj-maroon);">{{ $comment->blogPost->title_en }}</a>
                        <span style="font-size:11px; color:{{ $djCommentStatusColor }}; background:{{ $djCommentStatusBg }}; padding:2px 10px; border-radius:999px;">{{ __('blog_comments.status_'.$comment->status) }}</span>
                    </div>
                    <p style="font-size:13.5px; color:#8a6b70;">{{ str($comment->comment)->limit(120) }}</p>
                    <p style="font-size:11.5px; color:#a68b8f; margin-top:4px;">{{ $comment->created_at->format('M j, Y') }}</p>

                    <div style="display:flex; gap:14px; margin-top:8px;">
                        @if ($comment->status === 'pending')
                            <a href="{{ route('blog.show', $comment->blogPost) }}" style="font-size:12.5px; color:var(--dj-maroon); text-decoration:underline;">{{ __('general.edit') }}</a>
                        @endif
                        <form method="POST" action="{{ route('blog.comments.destroy', $comment) }}" onsubmit="return confirm('{{ __('blog_comments.confirm_delete') }}')">
                            @csrf @method('DELETE')
                            <button type="submit" style="font-size:12.5px; color:#b42318; text-decoration:underline;">{{ __('blog_comments.delete_comment') }}</button>
                        </form>
                    </div>
                </div>
            @endforeach

            <div class="mt-6">{{ $comments->links() }}</div>
        @endif
    </div>
@endsection
