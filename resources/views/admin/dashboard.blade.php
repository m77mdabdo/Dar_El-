@extends('admin.layout')

@section('title', __('admin.dashboard.title'))

@section('content')
    <p class="text-[var(--dj-rose-dust)] text-sm mb-5">{{ __('admin.dashboard.welcome', ['name' => Auth::user()->name]) }}</p>

    {{-- ===== STAT CARDS ===== --}}
    @php
        $djStatIcons = [
            'cart' => 'M2.25 3h1.386c.51 0 .955.343 1.087.836l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 1.71-4.804 1.968-6.723a.75.75 0 0 0-.65-.827H5.106M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z',
            'currency' => 'M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
            'users' => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z',
            'tag' => 'M5.25 3A2.25 2.25 0 0 0 3 5.25v5.379a3 3 0 0 0 .879 2.121l9.371 9.371a3 3 0 0 0 4.242 0l4.75-4.75a3 3 0 0 0 0-4.242l-9.371-9.371A3 3 0 0 0 10.629 3H5.25ZM6.375 9a1.125 1.125 0 1 1 0-2.25A1.125 1.125 0 0 1 6.375 9Z',
            'heart' => 'M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z',
            'envelope' => 'M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75',
            'bell' => 'M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0',
            'warning' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z',
        ];

        $djCards = [
            ['label' => __('admin.dashboard.total_orders'), 'value' => number_format($summary['total_orders']), 'href' => route('admin.orders.index'), 'icon' => 'cart'],
            ['label' => __('admin.dashboard.today_orders'), 'value' => number_format($summary['today_orders']), 'icon' => 'cart'],
            ['label' => __('admin.dashboard.pending_orders'), 'value' => number_format($summary['pending_orders']), 'href' => route('admin.orders.index', ['status' => 'pending']), 'icon' => 'cart'],
            ['label' => __('admin.dashboard.completed_orders'), 'value' => number_format($summary['completed_orders']), 'href' => route('admin.orders.index', ['status' => 'delivered']), 'icon' => 'cart'],
            ['label' => __('admin.dashboard.cancelled_orders'), 'value' => number_format($summary['cancelled_orders']), 'href' => route('admin.orders.index', ['status' => 'cancelled']), 'icon' => 'cart'],
            ['label' => __('admin.dashboard.total_revenue'), 'value' => number_format($summary['total_revenue']).' EGP', 'icon' => 'currency'],
            ['label' => __('admin.dashboard.today_revenue'), 'value' => number_format($summary['today_revenue']).' EGP', 'icon' => 'currency'],
            ['label' => __('admin.dashboard.monthly_revenue'), 'value' => number_format($summary['monthly_revenue']).' EGP', 'icon' => 'currency'],
            ['label' => __('admin.dashboard.total_customers'), 'value' => number_format($summary['total_customers']), 'icon' => 'users'],
            ['label' => __('admin.dashboard.new_customers'), 'value' => number_format($summary['new_customers']), 'icon' => 'users'],
            ['label' => __('admin.dashboard.products'), 'value' => number_format($summary['products']), 'href' => route('admin.products.index'), 'icon' => 'tag'],
            ['label' => __('admin.dashboard.categories'), 'value' => number_format($summary['categories']), 'href' => route('admin.categories.index'), 'icon' => 'tag'],
            ['label' => __('admin.dashboard.wishlist_items'), 'value' => number_format($summary['wishlist_items']), 'icon' => 'heart'],
            ['label' => __('admin.dashboard.messages'), 'value' => number_format($summary['unread_messages']), 'href' => route('admin.contact-messages.index'), 'icon' => 'envelope'],
            ['label' => __('admin.dashboard.unread_notifications'), 'value' => number_format($notifUnreadCount ?? 0), 'href' => route('admin.notifications.index'), 'icon' => 'bell'],
            ['label' => __('admin.dashboard.low_stock_products'), 'value' => number_format($summary['low_stock_count']), 'href' => route('admin.products.index', ['stock_status' => 'low_stock']), 'warn' => $summary['low_stock_count'] > 0, 'icon' => 'warning'],
            ['label' => __('admin.dashboard.out_of_stock_products'), 'value' => number_format($summary['out_of_stock_count']), 'href' => route('admin.products.index', ['stock_status' => 'out_of_stock']), 'warn' => $summary['out_of_stock_count'] > 0, 'icon' => 'warning'],
        ];
    @endphp
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3 sm:gap-4 mb-8">
        @foreach ($djCards as $card)
            @php $djTag = isset($card['href']) ? 'a' : 'div'; @endphp
            <{{ $djTag }} @if(isset($card['href'])) href="{{ $card['href'] }}" @endif
                class="dj-admin-stat-card {{ ($card['warn'] ?? false) ? 'dj-admin-warn' : '' }}"
            >
                <span class="dj-admin-stat-icon">
                    <svg class="w-4.5 h-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $djStatIcons[$card['icon']] }}"/></svg>
                </span>
                <p class="dj-admin-stat-label truncate">{{ $card['label'] }}</p>
                <p class="dj-admin-stat-value truncate">{{ $card['value'] }}</p>
            </{{ $djTag }}>
        @endforeach
    </div>

    {{-- ===== CHARTS ===== --}}
    @php
        $djSalesChart = [
            'type' => 'line',
            'data' => [
                'labels' => $charts['labels'],
                'datasets' => [
                    ['label' => __('admin.dashboard.total_revenue'), 'data' => $charts['revenue_series'], 'borderColor' => '#601526', 'backgroundColor' => 'rgba(96,21,38,0.08)', 'pointBackgroundColor' => '#601526', 'pointRadius' => 2, 'tension' => 0.35, 'fill' => true, 'yAxisID' => 'y'],
                    ['label' => __('admin.dashboard.total_orders'), 'data' => $charts['orders_series'], 'borderColor' => '#D4A574', 'backgroundColor' => 'rgba(212,165,116,0.12)', 'pointBackgroundColor' => '#D4A574', 'pointRadius' => 2, 'tension' => 0.35, 'fill' => true, 'yAxisID' => 'y1'],
                ],
            ],
            'options' => [
                'responsive' => true, 'maintainAspectRatio' => false,
                'interaction' => ['mode' => 'index', 'intersect' => false],
                'scales' => [
                    'y' => ['position' => 'left', 'beginAtZero' => true, 'grid' => ['color' => 'rgba(60,11,23,.06)']],
                    'y1' => ['position' => 'right', 'beginAtZero' => true, 'grid' => ['drawOnChartArea' => false]],
                    'x' => ['grid' => ['display' => false]],
                ],
            ],
        ];

        $djBrandPalette = ['#601526', '#D4A574', '#9C5064', '#7A2038', '#3C0B17', '#E8C39A'];

        $djStatusChart = [
            'type' => 'doughnut',
            'data' => [
                'labels' => $charts['status_labels'],
                'datasets' => [['data' => $charts['status_series'], 'backgroundColor' => $djBrandPalette, 'borderColor' => '#fff', 'borderWidth' => 2]],
            ],
            'options' => ['responsive' => true, 'maintainAspectRatio' => false, 'cutout' => '62%'],
        ];

        $djTopProductsChart = [
            'type' => 'bar',
            'data' => ['labels' => $charts['top_products_labels'], 'datasets' => [['label' => __('admin.dashboard.chart_top_products'), 'data' => $charts['top_products_series'], 'backgroundColor' => '#601526', 'borderRadius' => 6, 'maxBarThickness' => 22]]],
            'options' => ['indexAxis' => 'y', 'responsive' => true, 'maintainAspectRatio' => false, 'plugins' => ['legend' => ['display' => false]], 'scales' => ['x' => ['grid' => ['color' => 'rgba(60,11,23,.06)']], 'y' => ['grid' => ['display' => false]]]],
        ];

        $djTopWishlistChart = [
            'type' => 'bar',
            'data' => ['labels' => $charts['top_wishlist_labels'], 'datasets' => [['label' => __('admin.dashboard.chart_top_wishlist'), 'data' => $charts['top_wishlist_series'], 'backgroundColor' => '#9C5064', 'borderRadius' => 6, 'maxBarThickness' => 22]]],
            'options' => ['indexAxis' => 'y', 'responsive' => true, 'maintainAspectRatio' => false, 'plugins' => ['legend' => ['display' => false]], 'scales' => ['x' => ['grid' => ['color' => 'rgba(60,11,23,.06)']], 'y' => ['grid' => ['display' => false]]]],
        ];

        $djInventoryChart = [
            'type' => 'doughnut',
            'data' => [
                'labels' => [__('admin.dashboard.in_stock'), __('admin.dashboard.low_stock_products'), __('admin.dashboard.out_of_stock_products')],
                'datasets' => [['data' => [
                    max(0, $summary['products'] - $summary['low_stock_count'] - $summary['out_of_stock_count']),
                    $summary['low_stock_count'],
                    $summary['out_of_stock_count'],
                ], 'backgroundColor' => ['#601526', '#E8C39A', '#9C5064'], 'borderColor' => '#fff', 'borderWidth' => 2]],
            ],
            'options' => ['responsive' => true, 'maintainAspectRatio' => false, 'cutout' => '62%'],
        ];
    @endphp
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6 mb-8">
        <div class="dj-admin-card p-4 lg:col-span-2">
            <h2 class="font-semibold mb-3 text-sm sm:text-base text-[var(--dj-maroon-dark)]">{{ __('admin.dashboard.chart_sales') }}</h2>
            <div class="relative h-64 sm:h-72">
                <canvas class="dj-admin-chart w-full h-full" data-config='@json($djSalesChart)'></canvas>
            </div>
        </div>

        <div class="dj-admin-card p-4">
            <h2 class="font-semibold mb-3 text-sm sm:text-base text-[var(--dj-maroon-dark)]">{{ __('admin.dashboard.chart_orders_by_status') }}</h2>
            <div class="relative h-64 sm:h-72">
                @if (array_sum($charts['status_series']))
                    <canvas class="dj-admin-chart w-full h-full" data-config='@json($djStatusChart)'></canvas>
                @else
                    <p class="text-sm text-[var(--dj-rose-dust)] text-center py-16">{{ __('admin.dashboard.no_data') }}</p>
                @endif
            </div>
        </div>

        <div class="dj-admin-card p-4">
            <h2 class="font-semibold mb-3 text-sm sm:text-base text-[var(--dj-maroon-dark)]">{{ __('admin.dashboard.chart_top_products') }}</h2>
            <div class="relative h-64 sm:h-72">
                @if (count($charts['top_products_labels']))
                    <canvas class="dj-admin-chart w-full h-full" data-config='@json($djTopProductsChart)'></canvas>
                @else
                    <p class="text-sm text-[var(--dj-rose-dust)] text-center py-16">{{ __('admin.dashboard.no_data') }}</p>
                @endif
            </div>
        </div>

        <div class="dj-admin-card p-4">
            <h2 class="font-semibold mb-3 text-sm sm:text-base text-[var(--dj-maroon-dark)]">{{ __('admin.dashboard.chart_top_wishlist') }}</h2>
            <div class="relative h-64 sm:h-72">
                @if (count($charts['top_wishlist_labels']))
                    <canvas class="dj-admin-chart w-full h-full" data-config='@json($djTopWishlistChart)'></canvas>
                @else
                    <p class="text-sm text-[var(--dj-rose-dust)] text-center py-16">{{ __('admin.dashboard.no_data') }}</p>
                @endif
            </div>
        </div>

        <div class="dj-admin-card p-4">
            <h2 class="font-semibold mb-3 text-sm sm:text-base text-[var(--dj-maroon-dark)]">{{ __('admin.dashboard.chart_inventory') }}</h2>
            <div class="relative h-64 sm:h-72">
                <canvas class="dj-admin-chart w-full h-full" data-config='@json($djInventoryChart)'></canvas>
            </div>
        </div>
    </div>

    {{-- ===== RECENT ACTIVITY ===== --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
        <div class="dj-admin-card">
            <div class="dj-admin-card-header">
                <span>{{ __('admin.dashboard.recent_orders') }}</span>
                <a href="{{ route('admin.orders.index') }}" class="dj-admin-link">{{ __('admin.view_all') }}</a>
            </div>
            <div>
                @forelse ($recentOrders as $order)
                    <a href="{{ route('admin.orders.show', $order) }}" class="flex justify-between gap-3 px-4 py-3 text-sm border-t border-[var(--dj-cream-2)] first:border-t-0 hover:bg-[var(--dj-cream)] transition-colors">
                        <span class="truncate">{{ $order->order_number }} &middot; {{ $order->customer_name }}</span>
                        <span class="shrink-0 font-semibold text-[var(--dj-maroon)]">{{ number_format($order->total) }} EGP</span>
                    </a>
                @empty
                    <p class="px-4 py-6 text-sm text-[var(--dj-rose-dust)]">{{ __('admin.dashboard.no_orders') }}</p>
                @endforelse
            </div>
        </div>

        <div class="dj-admin-card">
            <div class="dj-admin-card-header">
                <span>{{ __('admin.dashboard.recent_customers') }}</span>
                <span class="text-xs text-stone-300 cursor-not-allowed" title="{{ __('admin.soon') }}">{{ __('admin.view_all') }}</span>
            </div>
            <div>
                @forelse ($recentCustomers as $customer)
                    <div class="flex justify-between gap-3 px-4 py-3 text-sm border-t border-[var(--dj-cream-2)] first:border-t-0">
                        <span class="truncate">{{ $customer->name }}</span>
                        <span class="shrink-0 text-[var(--dj-rose-dust)] text-xs">{{ $customer->created_at->diffForHumans() }}</span>
                    </div>
                @empty
                    <p class="px-4 py-6 text-sm text-[var(--dj-rose-dust)]">{{ __('admin.dashboard.no_customers') }}</p>
                @endforelse
            </div>
        </div>

        <div class="dj-admin-card">
            <div class="dj-admin-card-header">
                <span>{{ __('admin.dashboard.recent_messages') }}</span>
                <a href="{{ route('admin.contact-messages.index') }}" class="dj-admin-link">{{ __('admin.view_all') }}</a>
            </div>
            <div>
                @forelse ($recentMessages as $message)
                    <div class="px-4 py-3 text-sm border-t border-[var(--dj-cream-2)] first:border-t-0">
                        <p class="font-semibold truncate text-[var(--dj-ink)]">{{ $message->name }} <span class="text-[var(--dj-rose-dust)] font-normal">({{ $message->email }})</span></p>
                        <p class="text-[var(--dj-rose-dust)] truncate">{{ Str::limit($message->message, 80) }}</p>
                    </div>
                @empty
                    <p class="px-4 py-6 text-sm text-[var(--dj-rose-dust)]">{{ __('admin.dashboard.no_messages') }}</p>
                @endforelse
            </div>
        </div>

        <div class="dj-admin-card">
            <div class="dj-admin-card-header">
                <span>{{ __('admin.dashboard.recent_notifications') }}</span>
                <a href="{{ route('admin.notifications.index') }}" class="dj-admin-link">{{ __('admin.view_all') }}</a>
            </div>
            <div>
                @forelse ($recentNotifications as $notification)
                    @include('admin.partials.notification-item', ['notification' => $notification])
                @empty
                    <p class="px-4 py-6 text-sm text-[var(--dj-rose-dust)]">{{ __('admin.dashboard.no_notifications') }}</p>
                @endforelse
            </div>
        </div>

        <div class="dj-admin-card lg:col-span-2">
            <div class="dj-admin-card-header">
                <span>{{ __('admin.dashboard.low_stock_section') }}</span>
                <a href="{{ route('admin.products.index', ['stock_status' => 'low_stock']) }}" class="dj-admin-link">{{ __('admin.view_all') }}</a>
            </div>
            <div>
                @forelse ($lowStockProducts as $product)
                    <a href="{{ route('admin.products.edit', $product) }}" class="flex justify-between gap-3 px-4 py-3 text-sm border-t border-[var(--dj-cream-2)] first:border-t-0 hover:bg-[var(--dj-cream)] transition-colors">
                        <span class="truncate">{{ trans_field($product, 'name') }} <span class="text-[var(--dj-rose-dust)]">— {{ $product->category?->name_en }}</span></span>
                        <span class="dj-admin-badge dj-admin-badge-gold shrink-0">{{ (int) $product->total_stock }} {{ __('admin.dashboard.products') }}</span>
                    </a>
                @empty
                    <p class="px-4 py-6 text-sm text-[var(--dj-rose-dust)]">{{ __('admin.dashboard.no_low_stock') }}</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
