@extends('admin.layout')

@section('title', __('reports.customers_title'))

@section('content')
    <p class="dj-admin-hint mb-4">{{ __('reports.customers_subtitle') }}</p>

    <form method="GET" class="flex flex-wrap items-end gap-2 mb-6">
        <div>
            <label class="dj-admin-label">{{ __('reports.date_from') }}</label>
            <input type="date" name="from" value="{{ $from->toDateString() }}" class="dj-admin-input w-auto">
        </div>
        <div>
            <label class="dj-admin-label">{{ __('reports.date_to') }}</label>
            <input type="date" name="to" value="{{ $to->toDateString() }}" class="dj-admin-input w-auto">
        </div>
        <button class="dj-admin-btn dj-admin-btn-secondary">{{ __('reports.apply') }}</button>
    </form>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4 mb-8">
        <div class="dj-admin-stat-card">
            <p class="dj-admin-stat-label truncate">{{ __('reports.customers_total') }}</p>
            <p class="dj-admin-stat-value truncate">{{ number_format($totalCustomers) }}</p>
        </div>
        <div class="dj-admin-stat-card">
            <p class="dj-admin-stat-label truncate">{{ __('reports.customers_new') }}</p>
            <p class="dj-admin-stat-value truncate">{{ number_format($newCustomers) }}</p>
        </div>
        <div class="dj-admin-stat-card">
            <p class="dj-admin-stat-label truncate">{{ __('reports.customers_returning') }}</p>
            <p class="dj-admin-stat-value truncate">{{ number_format($returningCustomers) }}</p>
        </div>
    </div>

    @foreach ([
        ['title' => __('reports.customers_top_by_orders'), 'rows' => $topByOrderCount],
        ['title' => __('reports.customers_top_by_spend'), 'rows' => $topBySpend],
    ] as $djSection)
        <div class="dj-admin-card dj-admin-table-wrap mb-6">
            <div class="dj-admin-card-header"><span>{{ $djSection['title'] }}</span></div>
            <table class="dj-admin-table">
                <thead>
                    <tr>
                        <th>{{ __('reports.customers_customer') }}</th>
                        <th>{{ __('reports.customers_orders_count') }}</th>
                        <th>{{ __('reports.customers_total_spent') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($djSection['rows'] as $djRow)
                        <tr>
                            <td class="font-medium text-[var(--dj-ink)]">{{ $djRow->user->name ?? '—' }}</td>
                            <td>{{ number_format($djRow->orders_count) }}</td>
                            <td>{{ number_format($djRow->total_spent) }} EGP</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="dj-admin-table-empty">{{ __('reports.customers_no_data') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endforeach
@endsection
