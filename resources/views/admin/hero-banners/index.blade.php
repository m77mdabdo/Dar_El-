@extends('admin.layout')

@section('title', __('hero_banners.title'))

@section('content')
    <p class="dj-admin-hint mb-4">{{ __('hero_banners.live_hint') }}</p>

    <div class="flex justify-end mb-4">
        <a href="{{ route('admin.hero-banners.create') }}" class="dj-admin-btn dj-admin-btn-primary">+ {{ __('hero_banners.add_hero_banner') }}</a>
    </div>

    <div class="dj-admin-card dj-admin-table-wrap">
        @if ($banners->isNotEmpty())
            <p class="dj-admin-hint px-4 pt-3">{{ __('hero_banners.drag_to_reorder') }}</p>
        @endif
        <table class="dj-admin-table">
            <thead>
                <tr>
                    <th></th>
                    <th></th>
                    <th>{{ __('general.name') }}</th>
                    <th>{{ __('general.status') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody
                data-image-reorder
                data-reorder-url="{{ route('admin.hero-banners.reorder') }}"
                data-toast-success="{{ __('hero_banners.order_updated') }}"
                data-toast-error="{{ __('hero_banners.order_error') }}"
            >
                @forelse ($banners as $banner)
                    <tr data-image-id="{{ $banner->id }}">
                        <td class="cursor-grab text-[var(--dj-cream-3)]" title="{{ __('hero_banners.drag_to_reorder') }}">⠿</td>
                        <td>
                            <img src="{{ $banner->image_thumb }}" alt="" class="w-16 h-10 object-cover rounded-md border border-[var(--dj-cream-2)]">
                        </td>
                        <td class="font-medium text-[var(--dj-ink)]">{{ trans_field($banner, 'title') }}</td>
                        <td>
                            <span class="dj-admin-badge {{ $banner->is_active ? 'dj-admin-badge-success' : 'dj-admin-badge-neutral' }}">
                                {{ $banner->is_active ? __('general.active') : __('general.inactive') }}
                            </span>
                            <form method="POST" action="{{ route('admin.hero-banners.toggle-active', $banner) }}" class="inline" onsubmit="return confirm('{{ __('hero_banners.confirm_toggle_active') }}')">
                                @csrf
                                @method('PATCH')
                                <button class="dj-admin-link-muted text-xs">{{ $banner->is_active ? __('general.inactive') : __('general.active') }}</button>
                            </form>
                        </td>
                        <td class="text-end space-x-3 rtl:space-x-reverse">
                            <a href="{{ route('admin.hero-banners.edit', $banner) }}" class="dj-admin-link">{{ __('general.edit') }}</a>
                            <form method="POST" action="{{ route('admin.hero-banners.destroy', $banner) }}" class="inline" onsubmit="return confirm('{{ __('hero_banners.confirm_delete') }}')">
                                @csrf
                                @method('DELETE')
                                <button class="dj-admin-link-muted">{{ __('general.delete') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="dj-admin-table-empty">{{ __('hero_banners.no_hero_banners') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
