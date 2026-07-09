@extends('admin.layout')

@section('title', __('carts.title'))

@section('content')
    {{-- ===== STAT CARDS ===== --}}
    @php
        $djCartCards = [
            ['label' => __('carts.stat_active'), 'value' => number_format($stats['active']), 'href' => route('admin.carts.index', ['status' => 'active'])],
            ['label' => __('carts.stat_abandoned'), 'value' => number_format($stats['abandoned']), 'href' => route('admin.carts.index', ['status' => 'abandoned'])],
            ['label' => __('carts.stat_converted'), 'value' => number_format($stats['converted']), 'href' => route('admin.carts.index', ['status' => 'converted'])],
            ['label' => __('carts.stat_expired'), 'value' => number_format($stats['expired'])],
            ['label' => __('carts.stat_conversion_rate'), 'value' => $stats['conversion_rate'].'%'],
            ['label' => __('carts.stat_abandoned_value'), 'value' => number_format($stats['abandoned_value']).' EGP'],
            ['label' => __('carts.stat_reminders_today'), 'value' => number_format($stats['reminders_sent_today'])],
        ];
    @endphp
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3 sm:gap-4 mb-8">
        @foreach ($djCartCards as $card)
            @php $djTag = isset($card['href']) ? 'a' : 'div'; @endphp
            <{{ $djTag }} @if(isset($card['href'])) href="{{ $card['href'] }}" @endif class="dj-admin-stat-card">
                <p class="dj-admin-stat-label truncate">{{ $card['label'] }}</p>
                <p class="dj-admin-stat-value truncate">{{ $card['value'] }}</p>
            </{{ $djTag }}>
        @endforeach
    </div>

    {{-- ===== FILTERS ===== --}}
    <form method="GET" class="flex flex-wrap gap-2 mb-4">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('carts.search_placeholder') }}" class="dj-admin-input w-full sm:w-auto">

        <select name="status" onchange="this.form.submit()" class="dj-admin-input w-auto">
            <option value="">{{ __('carts.all_statuses') }}</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>{{ __('carts.status_active') }}</option>
            <option value="abandoned" {{ request('status') === 'abandoned' ? 'selected' : '' }}>{{ __('carts.status_abandoned') }}</option>
            <option value="converted" {{ request('status') === 'converted' ? 'selected' : '' }}>{{ __('carts.status_converted') }}</option>
            <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>{{ __('carts.status_expired') }}</option>
        </select>

        <input type="date" name="date_from" value="{{ request('date_from') }}" title="{{ __('carts.date_from') }}" class="dj-admin-input w-auto">
        <input type="date" name="date_to" value="{{ request('date_to') }}" title="{{ __('carts.date_to') }}" class="dj-admin-input w-auto">

        <button class="dj-admin-btn dj-admin-btn-secondary shrink-0">{{ __('general.search') }}</button>
    </form>

    {{-- ===== TABLE ===== --}}
    <form method="POST" action="{{ route('admin.carts.bulkReminder') }}">
        @csrf
        <div class="dj-admin-card dj-admin-table-wrap">
            <table class="dj-admin-table">
                <thead>
                    <tr>
                        <th></th>
                        <th>{{ __('carts.customer') }}</th>
                        <th>{{ __('carts.cart_items') }}</th>
                        <th>{{ __('carts.cart_total') }}</th>
                        <th>{{ __('carts.last_updated') }}</th>
                        <th>{{ __('carts.abandoned_duration') }}</th>
                        <th>{{ __('carts.reminder_count') }}</th>
                        <th>{{ __('general.status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($carts as $cart)
                        @php
                            $djCartBadge = match ($cart->status) {
                                'converted' => 'dj-admin-badge-success',
                                'abandoned' => 'dj-admin-badge-gold',
                                'expired' => 'dj-admin-badge-neutral',
                                default => 'dj-admin-badge-info',
                            };
                        @endphp
                        <tr>
                            <td><input type="checkbox" name="cart_ids[]" value="{{ $cart->id }}"></td>
                            <td class="font-medium text-[var(--dj-ink)]">
                                @if ($cart->user)
                                    <a href="{{ route('admin.customers.show', $cart->user) }}" class="dj-admin-link">{{ $cart->user->name }}</a>
                                    <p class="text-xs text-[var(--dj-rose-dust)]">{{ $cart->user->email }}</p>
                                @endif
                            </td>
                            <td>{{ $cart->items_count }}</td>
                            <td>{{ number_format($cart->total) }} EGP</td>
                            <td>{{ $cart->last_activity_at->format('M j, Y H:i') }}</td>
                            <td>{{ $cart->abandonedDuration() ?? '-' }}</td>
                            <td>{{ $cart->reminder_count }}</td>
                            <td><span class="dj-admin-badge {{ $djCartBadge }}">{{ __('carts.status_'.$cart->status) }}</span></td>
                            <td class="text-end space-x-3 rtl:space-x-reverse">
                                <a href="{{ route('admin.carts.show', $cart) }}" class="dj-admin-link">{{ __('general.view') }}</a>
                                @if ($cart->status !== 'converted' && $cart->items_count > 0)
                                    <form method="POST" action="{{ route('admin.carts.sendReminder', $cart) }}" class="inline">
                                        @csrf
                                        <button class="dj-admin-link-muted">{{ __('carts.send_reminder') }}</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="dj-admin-table-empty">{{ __('carts.no_carts_found') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($carts->isNotEmpty())
            <button type="submit" class="dj-admin-btn dj-admin-btn-secondary mt-4">{{ __('carts.send_bulk_reminder') }}</button>
        @endif
    </form>

    <div class="mt-4">{{ $carts->links() }}</div>

    {{-- ===== ANALYTICS ===== --}}
    @php
        $djCartStatusChart = [
            'type' => 'doughnut',
            'data' => [
                'labels' => [__('carts.status_active'), __('carts.status_abandoned'), __('carts.status_converted'), __('carts.status_expired')],
                'datasets' => [['data' => [
                    $charts['byStatus']['active'] ?? 0, $charts['byStatus']['abandoned'] ?? 0,
                    $charts['byStatus']['converted'] ?? 0, $charts['byStatus']['expired'] ?? 0,
                ], 'backgroundColor' => ['#D4A574', '#E8C39A', '#601526', '#9C5064'], 'borderColor' => '#fff', 'borderWidth' => 2]],
            ],
            'options' => ['responsive' => true, 'maintainAspectRatio' => false, 'cutout' => '62%'],
        ];

        $djCartTrendChart = [
            'type' => 'line',
            'data' => [
                'labels' => $charts['labels'],
                'datasets' => [
                    ['label' => __('carts.chart_abandoned_trend'), 'data' => $charts['abandonedSeries'], 'borderColor' => '#9C5064', 'backgroundColor' => 'rgba(156,80,100,0.1)', 'pointRadius' => 2, 'tension' => 0.35, 'fill' => true],
                    ['label' => __('carts.chart_conversion_trend'), 'data' => $charts['convertedSeries'], 'borderColor' => '#601526', 'backgroundColor' => 'rgba(96,21,38,0.08)', 'pointRadius' => 2, 'tension' => 0.35, 'fill' => true],
                ],
            ],
            'options' => ['responsive' => true, 'maintainAspectRatio' => false, 'scales' => ['y' => ['beginAtZero' => true, 'grid' => ['color' => 'rgba(60,11,23,.06)']], 'x' => ['grid' => ['display' => false]]]],
        ];

        $djCartTopProductsChart = [
            'type' => 'bar',
            'data' => ['labels' => $charts['topProducts']->map(fn ($r) => $r->product->name_en)->all(), 'datasets' => [['label' => __('carts.chart_top_products'), 'data' => $charts['topProducts']->pluck('qty')->all(), 'backgroundColor' => '#601526', 'borderRadius' => 6, 'maxBarThickness' => 22]]],
            'options' => ['indexAxis' => 'y', 'responsive' => true, 'maintainAspectRatio' => false, 'plugins' => ['legend' => ['display' => false]], 'scales' => ['x' => ['grid' => ['color' => 'rgba(60,11,23,.06)']], 'y' => ['grid' => ['display' => false]]]],
        ];
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6 mt-8">
        <div class="dj-admin-card p-4">
            <h2 class="font-semibold mb-3 text-sm sm:text-base text-[var(--dj-maroon-dark)]">{{ __('carts.chart_by_status') }}</h2>
            <div class="relative h-64">
                <canvas class="dj-admin-chart w-full h-full" data-config='@json($djCartStatusChart)'></canvas>
            </div>
        </div>
        <div class="dj-admin-card p-4 lg:col-span-2">
            <h2 class="font-semibold mb-3 text-sm sm:text-base text-[var(--dj-maroon-dark)]">{{ __('carts.chart_abandoned_trend') }} / {{ __('carts.chart_conversion_trend') }}</h2>
            <div class="relative h-64">
                <canvas class="dj-admin-chart w-full h-full" data-config='@json($djCartTrendChart)'></canvas>
            </div>
        </div>
    </div>

    <div class="dj-admin-card p-4 mt-6">
        <h2 class="font-semibold mb-3 text-sm sm:text-base text-[var(--dj-maroon-dark)]">{{ __('carts.chart_top_products') }}</h2>
        <div class="relative h-64">
            <canvas class="dj-admin-chart w-full h-full" data-config='@json($djCartTopProductsChart)'></canvas>
        </div>
    </div>
@endsection
