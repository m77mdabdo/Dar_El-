@extends('admin.layout')

@section('title', __('products.title'))

@section('content')
    <div class="flex flex-col sm:flex-row sm:justify-between gap-3 mb-4">
        <form method="GET" class="flex flex-wrap gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('products.search_placeholder') }}" class="dj-admin-input w-full sm:w-auto">
            <select name="status" onchange="this.form.submit()" class="dj-admin-input w-auto">
                <option value="">{{ __('products.all_statuses') }}</option>
                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>{{ __('products.status_draft') }}</option>
                <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>{{ __('products.status_scheduled') }}</option>
                <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>{{ __('products.status_published') }}</option>
                <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>{{ __('products.status_archived') }}</option>
            </select>
            <select name="stock_status" onchange="this.form.submit()" class="dj-admin-input w-auto">
                <option value="">{{ __('products.all_stock_levels') }}</option>
                <option value="in_stock" {{ request('stock_status') === 'in_stock' ? 'selected' : '' }}>{{ __('products.in_stock') }}</option>
                <option value="low_stock" {{ request('stock_status') === 'low_stock' ? 'selected' : '' }}>{{ __('products.low_stock') }}</option>
                <option value="out_of_stock" {{ request('stock_status') === 'out_of_stock' ? 'selected' : '' }}>{{ __('products.out_of_stock') }}</option>
            </select>
            <button class="dj-admin-btn dj-admin-btn-secondary shrink-0">{{ __('general.search') }}</button>
        </form>
        <a href="{{ route('admin.products.create') }}" class="dj-admin-btn dj-admin-btn-primary text-center">+ {{ __('products.add_product') }}</a>
    </div>

    <div
        x-data="djBulkTable()"
        data-bulk-action-url="{{ route('admin.products.bulk-action') }}"
        data-confirm-delete="{{ __('products.confirm_bulk_delete') }}"
        data-confirm-archive="{{ __('products.confirm_bulk_archive') }}"
        data-toast-success="{{ __('products.bulk_action_success') }}"
        data-toast-error="{{ __('products.bulk_action_error') }}"
        data-toast-bulk-delete-result="{{ __('products.bulk_delete_result') }}"
        data-shortcut-duplicate-table
    >
        <div class="dj-admin-bulk-bar" x-show="selected.length > 0" x-cloak>
            <span x-text="selected.length + ' {{ __('products.selected') }}'"></span>
            <button type="button" class="dj-admin-btn dj-admin-btn-sm dj-admin-btn-secondary" @click="bulkAction('publish')">{{ __('products.bulk_publish') }}</button>
            <button type="button" class="dj-admin-btn dj-admin-btn-sm dj-admin-btn-secondary" @click="bulkAction('archive')">{{ __('products.bulk_archive') }}</button>
            <button type="button" class="dj-admin-btn dj-admin-btn-sm dj-admin-btn-secondary" @click="bulkAction('duplicate')">{{ __('products.bulk_duplicate') }}</button>
            <button type="button" class="dj-admin-btn dj-admin-btn-sm dj-admin-btn-danger" @click="bulkAction('delete')">{{ __('products.bulk_delete') }}</button>
        </div>

        <div class="dj-admin-card dj-admin-table-wrap">
            <table class="dj-admin-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" @change="toggleAll($event)"></th>
                        <th>{{ __('general.name') }}</th>
                        <th>{{ __('products.category') }}</th>
                        <th>{{ __('products.price') }}</th>
                        <th>{{ __('products.stock') }}</th>
                        <th>{{ __('products.status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        @php
                            $stockStatus = $product->stockStatus((int) $product->total_stock);
                            $badge = $product->statusBadge();
                        @endphp
                        <tr>
                            <td><input type="checkbox" value="{{ $product->id }}" x-model="selected"></td>
                            <td class="font-medium text-[var(--dj-ink)]">{{ trans_field($product, 'name') }}</td>
                            <td>{{ trans_field($product->category, 'name') }}</td>
                            <td>{{ number_format($product->price) }} EGP</td>
                            <td>
                                {{ $product->total_stock }}
                                <span class="dj-admin-badge {{ $stockStatus['status'] === 'out_of_stock' ? 'dj-admin-badge-danger' : ($stockStatus['status'] === 'low_stock' ? 'dj-admin-badge-gold' : 'dj-admin-badge-success') }}">
                                    {{ $stockStatus['label'] }}
                                </span>
                            </td>
                            <td>
                                <span class="dj-admin-badge {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                            </td>
                            <td class="text-end space-x-3 rtl:space-x-reverse">
                                <a href="{{ route('admin.products.edit', $product) }}" class="dj-admin-link">{{ __('general.edit') }}</a>
                                <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="inline" onsubmit="return confirm('{{ __('products.confirm_delete') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="dj-admin-link-muted">{{ __('general.delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="dj-admin-table-empty">{{ __('products.no_products') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $products->links() }}</div>
@endsection
