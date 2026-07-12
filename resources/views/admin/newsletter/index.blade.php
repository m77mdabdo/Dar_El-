@extends('admin.layout')

@section('title', __('newsletter.title'))

@section('content')
    <div class="flex justify-end mb-4">
        <a href="{{ route('admin.newsletter.export') }}" class="dj-admin-btn dj-admin-btn-secondary">{{ __('newsletter.export_csv') }}</a>
    </div>

    <div class="dj-admin-card dj-admin-table-wrap">
        <table class="dj-admin-table">
            <thead>
                <tr>
                    <th>{{ __('general.email') }}</th>
                    <th>{{ __('newsletter.subscribed_at') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($subscribers as $subscriber)
                    <tr>
                        <td class="font-medium text-[var(--dj-ink)]">{{ $subscriber->email }}</td>
                        <td>{{ $subscriber->created_at->translatedFormat('M j, Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="dj-admin-table-empty">{{ __('newsletter.no_subscribers') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $subscribers->links() }}</div>
@endsection
