@extends('admin.layout')

@section('title', __('reports.sales_title'))

@section('content')
    <p class="dj-admin-hint mb-4">{{ __('reports.sales_subtitle') }}</p>

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
        <a href="{{ route('admin.reports.sales.export', ['from' => $from->toDateString(), 'to' => $to->toDateString()]) }}" class="dj-admin-btn dj-admin-btn-primary">{{ __('reports.export_csv') }}</a>
    </form>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4 mb-8">
        <div class="dj-admin-stat-card">
            <p class="dj-admin-stat-label truncate">{{ __('reports.sales_total_orders') }}</p>
            <p class="dj-admin-stat-value truncate">{{ number_format($totalOrders) }}</p>
        </div>
        <div class="dj-admin-stat-card">
            <p class="dj-admin-stat-label truncate">{{ __('reports.sales_completed_orders') }}</p>
            <p class="dj-admin-stat-value truncate">{{ number_format($completedOrders) }}</p>
        </div>
        <div class="dj-admin-stat-card {{ $cancelledOrders > 0 ? 'dj-admin-warn' : '' }}">
            <p class="dj-admin-stat-label truncate">{{ __('reports.sales_cancelled_orders') }}</p>
            <p class="dj-admin-stat-value truncate">{{ number_format($cancelledOrders) }}</p>
        </div>
        <div class="dj-admin-stat-card">
            <p class="dj-admin-stat-label truncate">{{ __('reports.sales_total_revenue') }}</p>
            <p class="dj-admin-stat-value truncate">{{ number_format($totalRevenue) }} EGP</p>
        </div>
        <div class="dj-admin-stat-card">
            <p class="dj-admin-stat-label truncate">{{ __('reports.sales_average_order_value') }}</p>
            <p class="dj-admin-stat-value truncate">{{ number_format($averageOrderValue) }} EGP</p>
        </div>
    </div>

    <div class="dj-admin-card dj-admin-table-wrap">
        <div class="dj-admin-card-header"><span>{{ __('reports.sales_daily_breakdown') }}</span></div>
        <table class="dj-admin-table">
            <thead>
                <tr>
                    <th>{{ __('reports.sales_day') }}</th>
                    <th>{{ __('reports.sales_orders_count') }}</th>
                    <th>{{ __('reports.sales_revenue') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($daily as $day)
                    <tr>
                        <td>{{ \Illuminate\Support\Carbon::parse($day->day)->translatedFormat('M j, Y') }}</td>
                        <td>{{ number_format($day->orders_count) }}</td>
                        <td>{{ number_format($day->revenue) }} EGP</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="dj-admin-table-empty">{{ __('reports.sales_no_data') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
