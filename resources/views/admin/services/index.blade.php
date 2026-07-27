@extends('admin.layout')

@section('title', __('services.title'))

@section('content')
    <p class="dj-admin-hint mb-4">{{ __('services.live_hint') }}</p>

    <div class="flex justify-end mb-4">
        <a href="{{ route('admin.services.create') }}" class="dj-admin-btn dj-admin-btn-primary">+ {{ __('services.add_service') }}</a>
    </div>

    <div class="dj-admin-card dj-admin-table-wrap">
        @if ($services->isNotEmpty())
            <p class="dj-admin-hint px-4 pt-3">{{ __('services.drag_to_reorder') }}</p>
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
                data-reorder-url="{{ route('admin.services.reorder') }}"
                data-toast-success="{{ __('services.order_updated') }}"
                data-toast-error="{{ __('services.order_error') }}"
            >
                @forelse ($services as $service)
                    <tr data-image-id="{{ $service->id }}">
                        <td class="cursor-grab text-[var(--dj-cream-3)]" title="{{ __('services.drag_to_reorder') }}">⠿</td>
                        <td>
                            <div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background:linear-gradient(135deg, var(--dj-maroon), var(--dj-maroon-soft));">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="var(--dj-gold)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">{!! $service->iconSvg() !!}</svg>
                            </div>
                        </td>
                        <td class="font-medium text-[var(--dj-ink)]">{{ trans_field($service, 'title') }}</td>
                        <td>
                            <span class="dj-admin-badge {{ $service->is_active ? 'dj-admin-badge-success' : 'dj-admin-badge-neutral' }}">
                                {{ $service->is_active ? __('general.active') : __('general.inactive') }}
                            </span>
                            <form method="POST" action="{{ route('admin.services.toggle-active', $service) }}" class="inline" onsubmit="return confirm('{{ __('services.confirm_toggle_active') }}')">
                                @csrf
                                @method('PATCH')
                                <button class="dj-admin-link-muted text-xs">{{ $service->is_active ? __('general.inactive') : __('general.active') }}</button>
                            </form>
                        </td>
                        <td class="text-end space-x-3 rtl:space-x-reverse">
                            <a href="{{ route('admin.services.edit', $service) }}" class="dj-admin-link">{{ __('general.edit') }}</a>
                            <form method="POST" action="{{ route('admin.services.destroy', $service) }}" class="inline" onsubmit="return confirm('{{ __('services.confirm_delete') }}')">
                                @csrf
                                @method('DELETE')
                                <button class="dj-admin-link-muted">{{ __('general.delete') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="dj-admin-table-empty">{{ __('services.no_services') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
