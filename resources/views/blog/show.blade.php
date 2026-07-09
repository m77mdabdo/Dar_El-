@extends('layouts.storefront')

@section('title', trans_field($post, 'title') . ' — Dar El-Jamila')
@section('meta_description', \Illuminate\Support\Str::limit(trans_field($post, 'excerpt'), 150))
@section('og_image', $post->cover_image ? setting_image_url($post->cover_image) : asset('favicon.ico'))

@section('content')
    <div class="max-w-3xl mx-auto px-4 sm:px-6 py-12">
        <a href="{{ route('blog.index') }}" style="font-size:13px; color:var(--dj-maroon); text-decoration:underline;">{{ app()->getLocale() === 'ar' ? '→' : '←' }} {{ __('Back to Journal') }}</a>

        @if ($post->cover_image)
            <div class="dj-photo-wrap dj-tint-maroon" style="aspect-ratio:16/9; border-radius:18px; overflow:hidden; margin:24px 0;">
                <img src="{{ setting_image_url($post->cover_image) }}" alt="">
            </div>
        @endif

        <div class="dj-blog-date" style="margin-bottom:10px;">{{ $post->published_at?->translatedFormat('F j, Y') }}</div>
        <h1 style="font-size:26px; color:var(--dj-maroon); margin-bottom:20px;">{{ trans_field($post, 'title') }}</h1>

        <div style="font-size:14.5px; line-height:2; color:#5a4448;">
            {!! nl2br(e(trans_field($post, 'body'))) !!}
        </div>

        <div style="margin-top:60px;">
            <div class="dj-section-title" style="text-align:left; padding:0 0 20px;">
                <h2 style="font-size:22px;">{{ $post->comments_count }} {{ __('blog_comments.count_label') }}</h2>
            </div>

            @forelse ($post->approvedComments as $comment)
                <div class="dj-comment-card">
                    <div class="dj-comment-avatar">{{ mb_substr($comment->name, 0, 1) }}</div>
                    <div class="dj-comment-body">
                        <span class="dj-comment-name">{{ $comment->name }}</span>
                        <span class="dj-comment-date">{{ $comment->created_at->format('M j, Y') }}</span>
                        <p class="dj-comment-text">{{ $comment->comment }}</p>
                    </div>
                </div>
            @empty
                <p style="font-size:14px; color:#8a6b70;">{{ __('blog_comments.be_first_to_comment') }}</p>
            @endforelse

            @guest
                <div style="margin-top:24px;">
                    <a href="{{ route('login', ['redirect' => route('blog.show', $post)]) }}" class="dj-modal-add" style="display:inline-block; text-align:center; text-decoration:none; width:auto; padding:14px 28px;">{{ __('blog_comments.login_to_comment') }}</a>
                </div>
            @else
                @if ($userComments->isNotEmpty())
                    <div style="margin-top:32px;">
                        <h3 style="font-size:16px; color:var(--dj-maroon); margin-bottom:12px;">{{ __('blog_comments.your_comments') }}</h3>
                        @foreach ($userComments as $own)
                            @php
                                $djOwnColor = match ($own->status) {
                                    'approved' => '#237a3f', 'rejected' => '#b42318', default => '#8a5a2a',
                                };
                                $djOwnBg = match ($own->status) {
                                    'approved' => '#e3f3e6', 'rejected' => '#fbe4e4', default => 'rgba(232,195,154,.35)',
                                };
                            @endphp
                            <div class="dj-comment-card" style="background:#fff; border:1px solid var(--dj-cream-2);">
                                <div class="dj-comment-avatar">{{ mb_substr($own->name, 0, 1) }}</div>
                                <div class="dj-comment-body">
                                    <span style="font-size:11px; color:{{ $djOwnColor }}; background:{{ $djOwnBg }}; padding:2px 10px; border-radius:999px;">{{ __('blog_comments.status_'.$own->status) }}</span>
                                    <span class="dj-comment-date">{{ $own->created_at->format('M j, Y') }}</span>

                                    @if ($own->status === 'pending')
                                        <form method="POST" action="{{ route('blog.comments.update', $own) }}" class="mt-2">
                                            @csrf @method('PATCH')
                                            <textarea name="comment" rows="2" minlength="5" maxlength="1000" required
                                                      style="width:100%; padding:10px 12px; border:1px solid var(--dj-cream-2); border-radius:10px; font-size:13.5px; margin-top:6px;">{{ old('comment', $own->comment) }}</textarea>
                                            <p style="font-size:11.5px; color:#8a5a2a; margin-top:4px;">{{ __('blog_comments.pending_notice') }}</p>
                                            <button type="submit" class="dj-admin-btn dj-admin-btn-secondary" style="margin-top:6px; font-size:12.5px; padding:8px 16px;">{{ __('blog_comments.update_comment') }}</button>
                                        </form>
                                    @else
                                        <p class="dj-comment-text">{{ $own->comment }}</p>
                                        @if ($own->status === 'rejected' && $own->rejection_reason)
                                            <p style="font-size:12px; color:#b42318; margin-top:4px;">{{ __('blog_comments.rejection_reason_label') }}: {{ $own->rejection_reason }}</p>
                                        @endif
                                    @endif

                                    <form method="POST" action="{{ route('blog.comments.destroy', $own) }}" onsubmit="return confirm('{{ __('blog_comments.confirm_delete') }}')" style="margin-top:6px;">
                                        @csrf @method('DELETE')
                                        <button type="submit" style="font-size:12px; color:#b42318; text-decoration:underline;">{{ __('blog_comments.delete_comment') }}</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div style="margin-top:32px;">
                    <h3 style="font-size:18px; color:var(--dj-maroon); margin-bottom:14px;">{{ __('blog_comments.write_comment') }}</h3>
                    <form method="POST" action="{{ route('blog.comments.store', $post) }}">
                        @csrf
                        <textarea name="comment" rows="4" minlength="5" maxlength="1000" required placeholder="{{ __('blog_comments.comment_placeholder') }}"
                                  style="width:100%; padding:12px 14px; border:1px solid var(--dj-cream-2); border-radius:10px; font-size:13.5px; margin-bottom:6px;">{{ old('comment') }}</textarea>
                        @error('comment') <p style="color:var(--dj-rose-dust); font-size:12px; margin-bottom:10px;">{{ $message }}</p> @enderror
                        <button type="submit" class="dj-modal-add" style="width:auto; padding:14px 28px;">{{ __('blog_comments.submit') }}</button>
                    </form>
                </div>
            @endguest
        </div>
    </div>
@endsection
