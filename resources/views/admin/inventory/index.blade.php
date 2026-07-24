@extends('admin.layout')

@section('title', __('inventory.title'))

@section('content')
    <p class="dj-admin-hint mb-4">{{ __('inventory.hint') }}</p>

    <form method="GET" class="flex flex-wrap gap-2 mb-4">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('products.search_placeholder') }}" class="dj-admin-input w-full sm:w-auto">
        <select name="category_id" onchange="this.form.submit()" class="dj-admin-input w-auto">
            <option value="">{{ __('inventory.all_categories') }}</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" {{ (string) request('category_id') === (string) $category->id ? 'selected' : '' }}>{{ trans_field($category, 'name') }}</option>
            @endforeach
        </select>
        <select name="stock_status" onchange="this.form.submit()" class="dj-admin-input w-auto">
            <option value="">{{ __('products.all_stock_levels') }}</option>
            <option value="in_stock" {{ request('stock_status') === 'in_stock' ? 'selected' : '' }}>{{ __('products.in_stock') }}</option>
            <option value="low_stock" {{ request('stock_status') === 'low_stock' ? 'selected' : '' }}>{{ __('products.low_stock') }}</option>
            <option value="out_of_stock" {{ request('stock_status') === 'out_of_stock' ? 'selected' : '' }}>{{ __('products.out_of_stock') }}</option>
        </select>
        <select name="sort" onchange="this.form.submit()" class="dj-admin-input w-auto">
            <option value="" {{ request('sort') ? '' : 'selected' }}>{{ __('inventory.sort_default') }}</option>
            <option value="stock_asc" {{ request('sort') === 'stock_asc' ? 'selected' : '' }}>{{ __('inventory.sort_stock_asc') }}</option>
            <option value="stock_desc" {{ request('sort') === 'stock_desc' ? 'selected' : '' }}>{{ __('inventory.sort_stock_desc') }}</option>
        </select>
        <button class="dj-admin-btn dj-admin-btn-secondary shrink-0">{{ __('general.search') }}</button>
    </form>

    @php
        $djStockBadgeClass = fn (string $status) => match ($status) {
            'out_of_stock' => 'dj-admin-badge-danger',
            'low_stock' => 'dj-admin-badge-gold',
            default => 'dj-admin-badge-success',
        };
    @endphp

    {{-- Desktop/tablet: a real table — dense, scannable, and the natural
         shape for "review many rows of numbers at once", which is the
         actual job this screen does. --}}
    <div class="dj-admin-card dj-admin-table-wrap hidden md:block">
        <table class="dj-admin-table">
            <thead>
                <tr>
                    <th>{{ __('general.name') }}</th>
                    <th>{{ __('products.category') }}</th>
                    <th>{{ __('inventory.stock_by_size') }}</th>
                    <th>{{ __('inventory.total_stock') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    @php $djStockStatus = $product->stockStatus((int) $product->total_stock); @endphp
                    <tr>
                        <td class="font-medium text-[var(--dj-ink)]">
                            <div class="flex items-center gap-3">
                                @if ($product->cover_image_src)
                                    <img src="{{ $product->cover_image_src }}" alt="" class="dj-inventory-thumb">
                                @else
                                    <span class="dj-inventory-thumb dj-inventory-thumb-empty">🛍️</span>
                                @endif
                                <span class="truncate">{{ trans_field($product, 'name') }}</span>
                            </div>
                        </td>
                        <td>{{ $product->category ? trans_field($product->category, 'name') : '—' }}</td>
                        <td>
                            @include('admin.inventory._stock_form', ['product' => $product])
                        </td>
                        <td>
                            <span class="dj-admin-badge {{ $djStockBadgeClass($djStockStatus['status']) }}" data-inventory-total-badge>
                                {{ (int) $product->total_stock }} — {{ $djStockStatus['label'] }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.products.edit', $product) }}" class="dj-admin-link">{{ __('inventory.manage_full_product') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="dj-admin-table-empty">{{ __('inventory.no_products') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Phone: the table above would force a lot of horizontal scrolling
         while trying to tap into small per-size number inputs at the same
         time — exactly the kind of touch-target squeeze this session's
         other admin-usability fixes (the related-products picker, the
         sidebar) moved away from. Cards stack everything vertically
         instead, same data, same inline-edit form, no scrolling-while-
         editing. --}}
    <div class="space-y-3 md:hidden">
        @forelse ($products as $product)
            @php $djStockStatus = $product->stockStatus((int) $product->total_stock); @endphp
            <div class="dj-admin-card p-4">
                <div class="flex items-center gap-3 mb-2">
                    @if ($product->cover_image_src)
                        <img src="{{ $product->cover_image_src }}" alt="" class="dj-inventory-thumb">
                    @else
                        <span class="dj-inventory-thumb dj-inventory-thumb-empty">🛍️</span>
                    @endif
                    <div class="min-w-0">
                        <p class="font-medium text-[var(--dj-ink)] truncate">{{ trans_field($product, 'name') }}</p>
                        <p class="dj-admin-hint">{{ $product->category ? trans_field($product->category, 'name') : '—' }}</p>
                    </div>
                </div>

                <span class="dj-admin-badge {{ $djStockBadgeClass($djStockStatus['status']) }} mb-3" data-inventory-total-badge>
                    {{ (int) $product->total_stock }} — {{ $djStockStatus['label'] }}
                </span>

                @include('admin.inventory._stock_form', ['product' => $product])

                <a href="{{ route('admin.products.edit', $product) }}" class="dj-admin-link block mt-3">{{ __('inventory.manage_full_product') }}</a>
            </div>
        @empty
            <div class="dj-admin-card dj-admin-table-empty">{{ __('inventory.no_products') }}</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $products->links() }}</div>

    <script>
        (function () {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

            function badgeClassFor(status) {
                if (status === 'out_of_stock') return 'dj-admin-badge-danger';
                if (status === 'low_stock') return 'dj-admin-badge-gold';
                return 'dj-admin-badge-success';
            }

            document.querySelectorAll('[data-inventory-stock-form]').forEach(function (form) {
                form.addEventListener('submit', async function (event) {
                    event.preventDefault();

                    const productId = form.dataset.productId;
                    const sizes = {};
                    form.querySelectorAll('input[data-size]').forEach(function (input) {
                        sizes[input.dataset.size] = input.value;
                    });

                    const saveBtn = form.querySelector('[data-inventory-save-btn]');
                    saveBtn.disabled = true;

                    try {
                        const response = await fetch(form.action, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: JSON.stringify({ sizes: sizes }),
                        });

                        const data = await response.json().catch(function () { return {}; });

                        if (!response.ok) {
                            throw new Error(data.message || 'Request failed');
                        }

                        // Both the desktop table row AND the mobile card
                        // render this same product with their own
                        // form/badge (only one is visible at a time via CSS,
                        // but both exist in the DOM) — updating every
                        // matching form means switching viewport width
                        // mid-session never shows stale numbers.
                        document.querySelectorAll('form[data-product-id="' + productId + '"]').forEach(function (matchingForm) {
                            matchingForm.querySelectorAll('input[data-size]').forEach(function (input) {
                                if (data.sizes && Object.prototype.hasOwnProperty.call(data.sizes, input.dataset.size)) {
                                    input.value = data.sizes[input.dataset.size];
                                }
                            });

                            const badge = matchingForm.closest('tr, .dj-admin-card')?.querySelector('[data-inventory-total-badge]');
                            if (badge && data.stock_status) {
                                badge.className = 'dj-admin-badge ' + badgeClassFor(data.stock_status.status);
                                badge.textContent = data.total_stock + ' — ' + data.stock_status.label;
                            }
                        });

                        window.djToast?.(data.message, 'success');
                    } catch (e) {
                        window.djToast?.(@json(__('inventory.save_error')), 'error');
                    } finally {
                        saveBtn.disabled = false;
                    }
                });
            });
        })();
    </script>
@endsection
