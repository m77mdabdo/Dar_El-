@extends('admin.layout')

@section('title', __('products.title'))

@section('content')
    <div class="flex flex-col sm:flex-row sm:justify-between gap-3 mb-4">
        <form method="GET" class="flex flex-wrap gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('products.search_placeholder') }}" class="dj-admin-input w-full sm:w-auto">
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

    <div class="dj-admin-card dj-admin-table-wrap">
        <table class="dj-admin-table">
            <thead>
                <tr>
                    <th>{{ __('general.name') }}</th>
                    <th>{{ __('products.category') }}</th>
                    <th>{{ __('products.price') }}</th>
                    <th>{{ __('products.stock') }}</th>
                    <th>{{ __('general.status') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    @php $status = $product->stockStatus((int) $product->total_stock); @endphp
                    <tr>
                        <td class="font-medium text-[var(--dj-ink)]">{{ $product->name_en }}</td>
                        <td>{{ $product->category->name_en }}</td>
                        <td>{{ number_format($product->price) }} EGP</td>
                        <td>
                            {{ $product->total_stock }}
                            <span class="dj-admin-badge {{ $status['status'] === 'out_of_stock' ? 'dj-admin-badge-danger' : ($status['status'] === 'low_stock' ? 'dj-admin-badge-gold' : 'dj-admin-badge-success') }}">
                                {{ $status['label'] }}
                            </span>
                        </td>
                        <td>
                            <span class="dj-admin-badge {{ $product->is_active ? 'dj-admin-badge-success' : 'dj-admin-badge-neutral' }}">{{ $product->is_active ? __('general.active') : __('general.inactive') }}</span>
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
                    <tr><td colspan="6" class="dj-admin-table-empty">{{ __('products.no_products') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $products->links() }}</div>
@endsection
