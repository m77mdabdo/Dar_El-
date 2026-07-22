{{--
    The real, storefront-affecting stock editor. The storefront, cart, and
    checkout all read stock from product_sizes exclusively — never from
    product_variants (see the warning banner on the Variants tab) — so this
    is the one screen that actually controls what a customer can buy.
--}}
<div class="dj-admin-card p-4 sm:p-6">
    <h2 class="font-semibold mb-1 text-[var(--dj-maroon-dark)]">{{ __('products.sizes_stock') }}</h2>
    <p class="dj-admin-hint mb-4">{{ __('products.sizes_stock_hint') }}</p>

    <form method="POST" action="{{ route('admin.products.sizes.update', $product) }}" class="space-y-3">
        @csrf
        @method('PATCH')

        @forelse ($product->sizes as $size)
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-end">
                <div>
                    <label class="dj-admin-label">{{ __('products.size_name') }}</label>
                    <input type="text" value="{{ $size->size }}" disabled class="dj-admin-input opacity-70">
                </div>
                <div>
                    <label class="dj-admin-label">{{ __('products.stock_quantity') }}</label>
                    <input type="number" name="sizes[{{ $size->size }}]" value="{{ old('sizes.'.$size->size, $size->stock) }}" min="0" class="dj-admin-input">
                </div>
            </div>
        @empty
            <p class="dj-admin-table-empty">{{ __('products.no_sizes_yet') }}</p>
        @endforelse

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-end pt-3 border-t border-[var(--dj-cream-2)]">
            <div>
                <label class="dj-admin-label">{{ __('products.new_size_name') }}</label>
                <input type="text" name="new_size_name" value="{{ old('new_size_name') }}" placeholder="{{ __('products.new_size_name_placeholder') }}" class="dj-admin-input">
                @error('new_size_name') <p class="dj-admin-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="dj-admin-label">{{ __('products.new_size_stock') }}</label>
                <input type="number" name="new_size_stock" value="{{ old('new_size_stock', 0) }}" min="0" class="dj-admin-input">
                @error('new_size_stock') <p class="dj-admin-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <button type="submit" class="dj-admin-btn dj-admin-btn-primary">{{ __('products.save_sizes') }}</button>
    </form>
</div>
