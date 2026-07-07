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
    </div>
@endsection
