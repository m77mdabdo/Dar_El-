@extends('admin.layout')

@section('title', __('admin.dashboard.title'))

@section('content')
    <p class="text-stone-500 text-sm mb-5">{{ __('admin.dashboard.welcome', ['name' => Auth::user()->name]) }}</p>

    {{-- ===== STAT CARDS ===== --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3 sm:gap-4 mb-8">
        @php
            $djCards = [
                ['label' => __('admin.dashboard.total_orders'), 'value' => number_format($summary['total_orders']), 'href' => route('admin.orders.index')],
                ['label' => __('admin.dashboard.today_orders'), 'value' => number_format($summary['today_orders'])],
                ['label' => __('admin.dashboard.pending_orders'), 'value' => number_format($summary['pending_orders']), 'href' => route('admin.orders.index', ['status' => 'pending'])],
                ['label' => __('admin.dashboard.completed_orders'), 'value' => number_format($summary['completed_orders']), 'href' => route('admin.orders.index', ['status' => 'delivered'])],
                ['label' => __('admin.dashboard.cancelled_orders'), 'value' => number_format($summary['cancelled_orders']), 'href' => route('admin.orders.index', ['status' => 'cancelled'])],
                ['label' => __('admin.dashboard.total_revenue'), 'value' => number_format($summary['total_revenue']).' EGP'],
                ['label' => __('admin.dashboard.today_revenue'), 'value' => number_format($summary['today_revenue']).' EGP'],
                ['label' => __('admin.dashboard.monthly_revenue'), 'value' => number_format($summary['monthly_revenue']).' EGP'],
                ['label' => __('admin.dashboard.total_customers'), 'value' => number_format($summary['total_customers'])],
                ['label' => __('admin.dashboard.new_customers'), 'value' => number_format($summary['new_customers'])],
                ['label' => __('admin.dashboard.products'), 'value' => number_format($summary['products']), 'href' => route('admin.products.index')],
                ['label' => __('admin.dashboard.categories'), 'value' => number_format($summary['categories']), 'href' => route('admin.categories.index')],
                ['label' => __('admin.dashboard.wishlist_items'), 'value' => number_format($summary['wishlist_items'])],
                ['label' => __('admin.dashboard.messages'), 'value' => number_format($summary['unread_messages']), 'href' => route('admin.contact-messages.index')],
                ['label' => __('admin.dashboard.unread_notifications'), 'value' => number_format($notifUnreadCount ?? 0), 'href' => route('admin.notifications.index')],
                ['label' => __('admin.dashboard.low_stock_products'), 'value' => number_format($summary['low_stock_count']), 'href' => route('admin.products.index', ['stock_status' => 'low_stock']), 'warn' => $summary['low_stock_count'] > 0],
                ['label' => __('admin.dashboard.out_of_stock_products'), 'value' => number_format($summary['out_of_stock_count']), 'href' => route('admin.products.index', ['stock_status' => 'out_of_stock']), 'warn' => $summary['out_of_stock_count'] > 0],
            ];
        @endphp
        @foreach ($djCards as $card)
            @php $djTag = isset($card['href']) ? 'a' : 'div'; @endphp
            <{{ $djTag }} @if(isset($card['href'])) href="{{ $card['href'] }}" @endif
                class="block bg-white border rounded-lg p-3 sm:p-4 {{ ($card['warn'] ?? false) ? 'border-red-300 ring-1 ring-red-200' : 'border-stone-200' }} {{ isset($card['href']) ? 'hover:border-stone-300 hover:shadow-sm transition' : '' }}"
            >
                <p class="text-[11px] sm:text-xs text-stone-500 uppercase tracking-wide truncate">{{ $card['label'] }}</p>
                <p class="text-lg sm:text-2xl font-semibold mt-1 truncate">{{ $card['value'] }}</p>
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
                    ['label' => __('admin.dashboard.total_revenue'), 'data' => $charts['revenue_series'], 'borderColor' => '#be123c', 'backgroundColor' => 'rgba(190,18,60,0.08)', 'tension' => 0.35, 'fill' => true, 'yAxisID' => 'y'],
                    ['label' => __('admin.dashboard.total_orders'), 'data' => $charts['orders_series'], 'borderColor' => '#78716c', 'backgroundColor' => 'rgba(120,113,108,0.08)', 'tension' => 0.35, 'fill' => true, 'yAxisID' => 'y1'],
                ],
            ],
            'options' => [
                'responsive' => true, 'maintainAspectRatio' => false,
                'interaction' => ['mode' => 'index', 'intersect' => false],
                'scales' => [
                    'y' => ['position' => 'left', 'beginAtZero' => true],
                    'y1' => ['position' => 'right', 'beginAtZero' => true, 'grid' => ['drawOnChartArea' => false]],
                ],
            ],
        ];

        $djStatusChart = [
            'type' => 'doughnut',
            'data' => [
                'labels' => $charts['status_labels'],
                'datasets' => [['data' => $charts['status_series'], 'backgroundColor' => ['#d97706', '#0ea5e9', '#8b5cf6', '#16a34a', '#dc2626']]],
            ],
            'options' => ['responsive' => true, 'maintainAspectRatio' => false],
        ];

        $djTopProductsChart = [
            'type' => 'bar',
            'data' => ['labels' => $charts['top_products_labels'], 'datasets' => [['label' => __('admin.dashboard.chart_top_products'), 'data' => $charts['top_products_series'], 'backgroundColor' => '#be123c']]],
            'options' => ['indexAxis' => 'y', 'responsive' => true, 'maintainAspectRatio' => false, 'plugins' => ['legend' => ['display' => false]]],
        ];

        $djTopWishlistChart = [
            'type' => 'bar',
            'data' => ['labels' => $charts['top_wishlist_labels'], 'datasets' => [['label' => __('admin.dashboard.chart_top_wishlist'), 'data' => $charts['top_wishlist_series'], 'backgroundColor' => '#0ea5e9']]],
            'options' => ['indexAxis' => 'y', 'responsive' => true, 'maintainAspectRatio' => false, 'plugins' => ['legend' => ['display' => false]]],
        ];

        $djInventoryChart = [
            'type' => 'doughnut',
            'data' => [
                'labels' => [__('admin.dashboard.in_stock'), __('admin.dashboard.low_stock_products'), __('admin.dashboard.out_of_stock_products')],
                'datasets' => [['data' => [
                    max(0, $summary['products'] - $summary['low_stock_count'] - $summary['out_of_stock_count']),
                    $summary['low_stock_count'],
                    $summary['out_of_stock_count'],
                ], 'backgroundColor' => ['#16a34a', '#d97706', '#dc2626']]],
            ],
            'options' => ['responsive' => true, 'maintainAspectRatio' => false],
        ];
    @endphp
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6 mb-8">
        <div class="bg-white border border-stone-200 rounded-lg p-4 lg:col-span-2">
            <h2 class="font-medium mb-3 text-sm sm:text-base">{{ __('admin.dashboard.chart_sales') }}</h2>
            <div class="relative h-64 sm:h-72">
                <canvas class="dj-admin-chart w-full h-full" data-config='@json($djSalesChart)'></canvas>
            </div>
        </div>

        <div class="bg-white border border-stone-200 rounded-lg p-4">
            <h2 class="font-medium mb-3 text-sm sm:text-base">{{ __('admin.dashboard.chart_orders_by_status') }}</h2>
            <div class="relative h-64 sm:h-72">
                @if (array_sum($charts['status_series']))
                    <canvas class="dj-admin-chart w-full h-full" data-config='@json($djStatusChart)'></canvas>
                @else
                    <p class="text-sm text-stone-400 text-center py-16">{{ __('admin.dashboard.no_data') }}</p>
                @endif
            </div>
        </div>

        <div class="bg-white border border-stone-200 rounded-lg p-4">
            <h2 class="font-medium mb-3 text-sm sm:text-base">{{ __('admin.dashboard.chart_top_products') }}</h2>
            <div class="relative h-64 sm:h-72">
                @if (count($charts['top_products_labels']))
                    <canvas class="dj-admin-chart w-full h-full" data-config='@json($djTopProductsChart)'></canvas>
                @else
                    <p class="text-sm text-stone-400 text-center py-16">{{ __('admin.dashboard.no_data') }}</p>
                @endif
            </div>
        </div>

        <div class="bg-white border border-stone-200 rounded-lg p-4">
            <h2 class="font-medium mb-3 text-sm sm:text-base">{{ __('admin.dashboard.chart_top_wishlist') }}</h2>
            <div class="relative h-64 sm:h-72">
                @if (count($charts['top_wishlist_labels']))
                    <canvas class="dj-admin-chart w-full h-full" data-config='@json($djTopWishlistChart)'></canvas>
                @else
                    <p class="text-sm text-stone-400 text-center py-16">{{ __('admin.dashboard.no_data') }}</p>
                @endif
            </div>
        </div>

        <div class="bg-white border border-stone-200 rounded-lg p-4">
            <h2 class="font-medium mb-3 text-sm sm:text-base">{{ __('admin.dashboard.chart_inventory') }}</h2>
            <div class="relative h-64 sm:h-72">
                <canvas class="dj-admin-chart w-full h-full" data-config='@json($djInventoryChart)'></canvas>
            </div>
        </div>
    </div>

    {{-- ===== RECENT ACTIVITY ===== --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
        <div class="bg-white border border-stone-200 rounded-lg">
            <div class="flex items-center justify-between px-4 py-3 border-b border-stone-200">
                <span class="font-medium text-sm sm:text-base">{{ __('admin.dashboard.recent_orders') }}</span>
                <a href="{{ route('admin.orders.index') }}" class="text-xs text-rose-700 hover:underline">{{ __('admin.view_all') }}</a>
            </div>
            <div class="divide-y divide-stone-100">
                @forelse ($recentOrders as $order)
                    <a href="{{ route('admin.orders.show', $order) }}" class="flex justify-between gap-3 px-4 py-3 text-sm hover:bg-stone-50">
                        <span class="truncate">{{ $order->order_number }} &middot; {{ $order->customer_name }}</span>
                        <span class="shrink-0 font-medium">{{ number_format($order->total) }} EGP</span>
                    </a>
                @empty
                    <p class="px-4 py-6 text-sm text-stone-500">{{ __('admin.dashboard.no_orders') }}</p>
                @endforelse
            </div>
        </div>

        <div class="bg-white border border-stone-200 rounded-lg">
            <div class="flex items-center justify-between px-4 py-3 border-b border-stone-200">
                <span class="font-medium text-sm sm:text-base">{{ __('admin.dashboard.recent_customers') }}</span>
                <span class="text-xs text-stone-300 cursor-not-allowed" title="{{ __('admin.soon') }}">{{ __('admin.view_all') }}</span>
            </div>
            <div class="divide-y divide-stone-100">
                @forelse ($recentCustomers as $customer)
                    <div class="flex justify-between gap-3 px-4 py-3 text-sm">
                        <span class="truncate">{{ $customer->name }}</span>
                        <span class="shrink-0 text-stone-400 text-xs">{{ $customer->created_at->diffForHumans() }}</span>
                    </div>
                @empty
                    <p class="px-4 py-6 text-sm text-stone-500">{{ __('admin.dashboard.no_customers') }}</p>
                @endforelse
            </div>
        </div>

        <div class="bg-white border border-stone-200 rounded-lg">
            <div class="flex items-center justify-between px-4 py-3 border-b border-stone-200">
                <span class="font-medium text-sm sm:text-base">{{ __('admin.dashboard.recent_messages') }}</span>
                <a href="{{ route('admin.contact-messages.index') }}" class="text-xs text-rose-700 hover:underline">{{ __('admin.view_all') }}</a>
            </div>
            <div class="divide-y divide-stone-100">
                @forelse ($recentMessages as $message)
                    <div class="px-4 py-3 text-sm">
                        <p class="font-medium truncate">{{ $message->name }} <span class="text-stone-400 font-normal">({{ $message->email }})</span></p>
                        <p class="text-stone-500 truncate">{{ Str::limit($message->message, 80) }}</p>
                    </div>
                @empty
                    <p class="px-4 py-6 text-sm text-stone-500">{{ __('admin.dashboard.no_messages') }}</p>
                @endforelse
            </div>
        </div>

        <div class="bg-white border border-stone-200 rounded-lg">
            <div class="flex items-center justify-between px-4 py-3 border-b border-stone-200">
                <span class="font-medium text-sm sm:text-base">{{ __('admin.dashboard.recent_notifications') }}</span>
                <a href="{{ route('admin.notifications.index') }}" class="text-xs text-rose-700 hover:underline">{{ __('admin.view_all') }}</a>
            </div>
            <div class="divide-y divide-stone-100">
                @forelse ($recentNotifications as $notification)
                    @include('admin.partials.notification-item', ['notification' => $notification])
                @empty
                    <p class="px-4 py-6 text-sm text-stone-500">{{ __('admin.dashboard.no_notifications') }}</p>
                @endforelse
            </div>
        </div>

        <div class="bg-white border border-stone-200 rounded-lg lg:col-span-2">
            <div class="flex items-center justify-between px-4 py-3 border-b border-stone-200">
                <span class="font-medium text-sm sm:text-base">{{ __('admin.dashboard.low_stock_section') }}</span>
                <a href="{{ route('admin.products.index', ['stock_status' => 'low_stock']) }}" class="text-xs text-rose-700 hover:underline">{{ __('admin.view_all') }}</a>
            </div>
            <div class="divide-y divide-stone-100">
                @forelse ($lowStockProducts as $product)
                    <a href="{{ route('admin.products.edit', $product) }}" class="flex justify-between gap-3 px-4 py-3 text-sm hover:bg-stone-50">
                        <span class="truncate">{{ trans_field($product, 'name') }} <span class="text-stone-400">— {{ $product->category?->name_en }}</span></span>
                        <span class="shrink-0 font-medium text-amber-600">{{ (int) $product->total_stock }} {{ __('admin.dashboard.products') }}</span>
                    </a>
                @empty
                    <p class="px-4 py-6 text-sm text-stone-500">{{ __('admin.dashboard.no_low_stock') }}</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
