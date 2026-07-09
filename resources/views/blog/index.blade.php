@extends('layouts.storefront')

@section('title', __('Blog') . ' — Dar El-Jamila')
@section('meta_description', __('Style tips, occasion looks, and behind-the-scenes from the Dar El-Jamila world'))

@section('content')
    <section class="dj-page-hero dj-photo-wrap dj-tint-maroon dj-strong">
        <img src="{{ setting_image_url($heroImage) }}" alt="">
        <div class="dj-mesh"><span></span><span></span><span></span></div>
        <div class="dj-particles" data-particles="12"></div>
        <div class="dj-lattice-bg"></div>
        <div class="dj-eyebrow">{{ __('Blog') }}</div>
        <h1>{{ __('Tales of Elegance') }}</h1>
        <p>{{ __('Style tips, occasion looks, and behind-the-scenes from the Dar El-Jamila world') }}</p>
    </section>

    <div class="dj-blog-grid">
        @forelse ($posts as $post)
            <a href="{{ route('blog.show', $post) }}" class="dj-blog-card dj-reveal">
                <div class="dj-blog-cover dj-photo-wrap dj-tint-maroon">
                    @if ($post->cover_image)
                        <img src="{{ setting_image_url($post->cover_image) }}" alt="">
                    @endif
                </div>
                <div class="dj-blog-body">
                    <div class="dj-blog-date">{{ $post->published_at?->translatedFormat('F j, Y') }} &middot; {{ $post->comments_count }} {{ __('blog_comments.count_label') }}</div>
                    <h3>{{ trans_field($post, 'title') }}</h3>
                    <p>{{ \Illuminate\Support\Str::limit(trans_field($post, 'excerpt'), 100) }}</p>
                    <span class="dj-read-more">{{ __('Read More →') }}</span>
                </div>
            </a>
        @empty
            <p style="grid-column:1/-1; text-align:center; color:#8a6b70;">{{ __('No posts yet.') }}</p>
        @endforelse
    </div>

    <div class="max-w-7xl mx-auto px-4 pb-16">
        {{ $posts->links() }}
    </div>
@endsection
