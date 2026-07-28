@extends('admin.layout')

@section('title', __('email_preview.title'))

@section('content')
    <p class="dj-admin-hint mb-4">{{ __('email_preview.subtitle') }}</p>

    @if (! $isLocal)
        <div class="dj-admin-card dj-admin-empty">
            <p class="font-semibold text-[var(--dj-maroon-dark)] mb-1">{{ __('email_preview.not_local_title') }}</p>
            <p>{{ __('email_preview.not_local_body') }}</p>
        </div>
    @else
        @foreach ($templates as $djGroupKey => $djGroupTemplates)
            <div class="mb-6">
                <h2 class="text-sm font-bold text-[var(--dj-maroon-dark)] uppercase tracking-wide mb-3">{{ __('email_preview.group_'.$djGroupKey) }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach ($djGroupTemplates as $djType => $djLabel)
                        <a href="{{ route('admin.email-preview.show', $djType) }}" target="_blank" rel="noopener" class="dj-admin-card p-4 hover:bg-[var(--dj-cream)] transition-colors">
                            <p class="font-medium text-[var(--dj-ink)]">{{ $djLabel }}</p>
                            <p class="dj-admin-link text-xs mt-1">{{ __('email_preview.preview') }} ↗</p>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    @endif
@endsection
