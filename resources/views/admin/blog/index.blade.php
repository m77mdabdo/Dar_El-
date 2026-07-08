@extends('admin.layout')

@section('title', __('blog.title'))

@section('content')
    <div class="flex flex-col sm:flex-row sm:justify-between gap-3 mb-4">
        <form method="GET" class="flex gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('blog.search_placeholder') }}" class="dj-admin-input w-full sm:w-auto">
            <button class="dj-admin-btn dj-admin-btn-secondary shrink-0">{{ __('general.search') }}</button>
        </form>
        <a href="{{ route('admin.blog.create') }}" class="dj-admin-btn dj-admin-btn-primary text-center">+ {{ __('blog.add_post') }}</a>
    </div>

    <div class="dj-admin-card dj-admin-table-wrap">
        <table class="dj-admin-table">
            <thead>
                <tr>
                    <th>{{ __('blog.cover') }}</th>
                    <th>{{ __('blog.post_title') }}</th>
                    <th>{{ __('general.published') }}</th>
                    <th>{{ __('general.date') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($posts as $post)
                    <tr>
                        <td>
                            @if ($post->cover_image)
                                <img src="{{ asset('storage/'.$post->cover_image) }}" class="w-12 h-12 object-cover rounded-lg border border-[var(--dj-cream-2)]">
                            @endif
                        </td>
                        <td class="font-medium text-[var(--dj-ink)]">{{ $post->title_en }}</td>
                        <td><span class="dj-admin-badge {{ $post->is_published ? 'dj-admin-badge-success' : 'dj-admin-badge-neutral' }}">{{ $post->is_published ? __('general.published') : __('general.draft') }}</span></td>
                        <td>{{ $post->published_at?->format('M j, Y') ?? '-' }}</td>
                        <td class="text-end space-x-3 rtl:space-x-reverse">
                            <a href="{{ route('admin.blog.edit', $post) }}" class="dj-admin-link">{{ __('general.edit') }}</a>
                            <form method="POST" action="{{ route('admin.blog.destroy', $post) }}" class="inline" onsubmit="return confirm('{{ __('blog.confirm_delete') }}')">
                                @csrf
                                @method('DELETE')
                                <button class="dj-admin-link-muted">{{ __('general.delete') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="dj-admin-table-empty">{{ __('blog.no_posts') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $posts->links() }}</div>
@endsection
