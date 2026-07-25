@extends('admin.layout')

@section('title', __('faqs.title'))

@section('content')
    <p class="dj-admin-hint mb-4">{{ __('faqs.live_hint') }}</p>

    <div class="flex justify-end mb-4">
        <a href="{{ route('admin.faqs.create') }}" class="dj-admin-btn dj-admin-btn-primary">+ {{ __('faqs.add_faq') }}</a>
    </div>

    <div class="dj-admin-card dj-admin-table-wrap">
        @if ($faqs->isNotEmpty())
            <p class="dj-admin-hint px-4 pt-3">{{ __('faqs.drag_to_reorder') }}</p>
        @endif
        <table class="dj-admin-table">
            <thead>
                <tr>
                    <th></th>
                    <th>{{ __('faqs.question_en') }}</th>
                    <th>{{ __('general.status') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody
                data-image-reorder
                data-reorder-url="{{ route('admin.faqs.reorder') }}"
                data-toast-success="{{ __('faqs.order_updated') }}"
                data-toast-error="{{ __('faqs.order_error') }}"
            >
                @forelse ($faqs as $faq)
                    <tr data-image-id="{{ $faq->id }}">
                        <td class="cursor-grab text-[var(--dj-cream-3)]" title="{{ __('faqs.drag_to_reorder') }}">⠿</td>
                        <td class="font-medium text-[var(--dj-ink)]">{{ trans_field($faq, 'question') }}</td>
                        <td>
                            <span class="dj-admin-badge {{ $faq->is_active ? 'dj-admin-badge-success' : 'dj-admin-badge-neutral' }}">
                                {{ $faq->is_active ? __('general.active') : __('general.inactive') }}
                            </span>
                            <form method="POST" action="{{ route('admin.faqs.toggle-active', $faq) }}" class="inline" onsubmit="return confirm('{{ __('faqs.confirm_toggle_active') }}')">
                                @csrf
                                @method('PATCH')
                                <button class="dj-admin-link-muted text-xs">{{ $faq->is_active ? __('general.inactive') : __('general.active') }}</button>
                            </form>
                        </td>
                        <td class="text-end space-x-3 rtl:space-x-reverse">
                            <a href="{{ route('admin.faqs.edit', $faq) }}" class="dj-admin-link">{{ __('general.edit') }}</a>
                            <form method="POST" action="{{ route('admin.faqs.destroy', $faq) }}" class="inline" onsubmit="return confirm('{{ __('faqs.confirm_delete') }}')">
                                @csrf
                                @method('DELETE')
                                <button class="dj-admin-link-muted">{{ __('general.delete') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="dj-admin-table-empty">{{ __('faqs.no_faqs') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
