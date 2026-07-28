@extends('admin.layout')

@section('title', __('reports.inventory_title'))

@section('content')
    <p class="dj-admin-hint mb-4">{{ __('reports.inventory_subtitle') }}</p>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4 mb-6">
        <div class="dj-admin-stat-card">
            <p class="dj-admin-stat-label truncate">{{ __('reports.inventory_total_valuation') }}</p>
            <p class="dj-admin-stat-value truncate">{{ number_format($totalValuation) }} EGP</p>
        </div>
        <a href="{{ route('admin.reports.inventory', ['stock_status' => 'low_stock']) }}" class="dj-admin-stat-card {{ $lowStockCount > 0 ? 'dj-admin-warn' : '' }}">
            <p class="dj-admin-stat-label truncate">{{ __('reports.inventory_low_stock') }}</p>
            <p class="dj-admin-stat-value truncate">{{ number_format($lowStockCount) }}</p>
        </a>
        <a href="{{ route('admin.reports.inventory', ['stock_status' => 'out_of_stock']) }}" class="dj-admin-stat-card {{ $outOfStockCount > 0 ? 'dj-admin-warn' : '' }}">
            <p class="dj-admin-stat-label truncate">{{ __('reports.inventory_out_of_stock') }}</p>
            <p class="dj-admin-stat-value truncate">{{ number_format($outOfStockCount) }}</p>
        </a>
    </div>

    <form method="GET" class="flex flex-wrap gap-2 mb-4">
        <select name="stock_status" onchange="this.form.submit()" class="dj-admin-input w-auto">
            <option value="">{{ __('products.all_stock_levels') }}</option>
            <option value="in_stock" {{ request('stock_status') === 'in_stock' ? 'selected' : '' }}>{{ __('products.in_stock') }}</option>
            <option value="low_stock" {{ request('stock_status') === 'low_stock' ? 'selected' : '' }}>{{ __('products.low_stock') }}</option>
            <option value="out_of_stock" {{ request('stock_status') === 'out_of_stock' ? 'selected' : '' }}>{{ __('products.out_of_stock') }}</option>
        </select>
        <select name="sort" onchange="this.form.submit()" class="dj-admin-input w-auto">
            <option value="" {{ request('sort') ? '' : 'selected' }}>{{ __('reports.inventory_sort_value_desc') }}</option>
            <option value="value_asc" {{ request('sort') === 'value_asc' ? 'selected' : '' }}>{{ __('reports.inventory_sort_value_asc') }}</option>
            <option value="stock_asc" {{ request('sort') === 'stock_asc' ? 'selected' : '' }}>{{ __('reports.inventory_sort_stock_asc') }}</option>
        </select>
    </form>

    @php
        $djStockBadgeClass = fn (string $status) => match ($status) {
            'out_of_stock' => 'dj-admin-badge-danger',
            'low_stock' => 'dj-admin-badge-gold',
            default => 'dj-admin-badge-success',
        };
    @endphp

    <div class="dj-admin-card dj-admin-table-wrap">
        <table class="dj-admin-table">
            <thead>
                <tr>
                    <th>{{ __('reports.inventory_product') }}</th>
                    <th>{{ __('reports.inventory_category') }}</th>
                    <th>{{ __('reports.inventory_stock') }}</th>
                    <th>{{ __('reports.inventory_price') }}</th>
                    <th>{{ __('reports.inventory_value') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    @php $djStockStatus = $product->stockStatus((int) $product->total_stock); @endphp
                    <tr>
                        <td class="font-medium text-[var(--dj-ink)]">{{ trans_field($product, 'name') }}</td>
                        <td>{{ $product->category ? trans_field($product->category, 'name') : '—' }}</td>
                        <td>
                            <span class="dj-admin-badge {{ $djStockBadgeClass($djStockStatus['status']) }}">
                                {{ (int) $product->total_stock }} — {{ $djStockStatus['label'] }}
                            </span>
                        </td>
                        <td>{{ number_format($product->price) }} EGP</td>
                        <td>{{ number_format($product->stock_value) }} EGP</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="dj-admin-table-empty">{{ __('reports.inventory_no_products') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $products->links() }}</div>
@endsection
