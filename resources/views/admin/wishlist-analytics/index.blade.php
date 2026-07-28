@extends('admin.layout')

@section('title', __('admin_wishlist.title'))

@section('content')
    <p class="dj-admin-hint mb-4">{{ __('admin_wishlist.subtitle') }}</p>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4 mb-6">
        <div class="dj-admin-stat-card">
            <p class="dj-admin-stat-label truncate">{{ __('admin_wishlist.stat_total_adds') }}</p>
            <p class="dj-admin-stat-value truncate">{{ number_format($totalWishlistAdds) }}</p>
        </div>
        <a href="{{ route('admin.wishlist-analytics.index', ['stock_status' => 'low_stock']) }}" class="dj-admin-stat-card {{ $wishlistedLowStockCount > 0 ? 'dj-admin-warn' : '' }}">
            <p class="dj-admin-stat-label truncate">{{ __('admin_wishlist.stat_wishlisted_low_stock') }}</p>
            <p class="dj-admin-stat-value truncate">{{ number_format($wishlistedLowStockCount) }}</p>
        </a>
        <a href="{{ route('admin.wishlist-analytics.index', ['stock_status' => 'out_of_stock']) }}" class="dj-admin-stat-card {{ $wishlistedOutOfStockCount > 0 ? 'dj-admin-warn' : '' }}">
            <p class="dj-admin-stat-label truncate">{{ __('admin_wishlist.stat_wishlisted_out_of_stock') }}</p>
            <p class="dj-admin-stat-value truncate">{{ number_format($wishlistedOutOfStockCount) }}</p>
        </a>
    </div>

    <form method="GET" class="flex flex-wrap gap-2 mb-4">
        <select name="stock_status" onchange="this.form.submit()" class="dj-admin-input w-auto">
            <option value="">{{ __('admin_wishlist.all_stock_statuses') }}</option>
            <option value="in_stock" {{ request('stock_status') === 'in_stock' ? 'selected' : '' }}>{{ __('products.in_stock') }}</option>
            <option value="low_stock" {{ request('stock_status') === 'low_stock' ? 'selected' : '' }}>{{ __('products.low_stock') }}</option>
            <option value="out_of_stock" {{ request('stock_status') === 'out_of_stock' ? 'selected' : '' }}>{{ __('products.out_of_stock') }}</option>
        </select>
        <select name="sort" onchange="this.form.submit()" class="dj-admin-input w-auto">
            <option value="" {{ request('sort') ? '' : 'selected' }}>{{ __('admin_wishlist.sort_most_wishlisted') }}</option>
            <option value="stock_asc" {{ request('sort') === 'stock_asc' ? 'selected' : '' }}>{{ __('admin_wishlist.sort_lowest_stock') }}</option>
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
                    <th>{{ __('admin_wishlist.product') }}</th>
                    <th>{{ __('admin_wishlist.category') }}</th>
                    <th>{{ __('admin_wishlist.wishlist_count') }}</th>
                    <th>{{ __('admin_wishlist.stock') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    @php $djStockStatus = $product->stockStatus((int) $product->total_stock); @endphp
                    <tr>
                        <td class="font-medium text-[var(--dj-ink)]">{{ trans_field($product, 'name') }}</td>
                        <td>{{ $product->category ? trans_field($product->category, 'name') : '—' }}</td>
                        <td>
                            <span class="dj-admin-badge dj-admin-badge-info">{{ number_format($product->wishlists_count) }}</span>
                        </td>
                        <td>
                            <span class="dj-admin-badge {{ $djStockBadgeClass($djStockStatus['status']) }}">
                                {{ (int) $product->total_stock }} — {{ $djStockStatus['label'] }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.products.edit', $product) }}" class="dj-admin-link">{{ __('inventory.manage_full_product') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="dj-admin-table-empty">{{ __('admin_wishlist.no_products') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $products->links() }}</div>
@endsection
