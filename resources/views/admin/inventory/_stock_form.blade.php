{{-- One form per product, all its sizes at once — submits to the exact
     same admin.products.sizes.update action the "Sizes & Stock" tab uses
     (Admin\ProductController::updateSizes()), just via fetch() here so
     reviewing many rows in a row doesn't mean a full page reload per edit.
     A plain POST fallback still works with JS disabled (method-spoofed
     PATCH, redirects back here) — the inline-edit JS is a progressive
     enhancement, not the only way this form functions. --}}
@if ($product->sizes->isEmpty())
    <span class="dj-admin-hint">{{ __('inventory.no_sizes_short') }}</span>
@else
    <form
        method="POST"
        action="{{ route('admin.products.sizes.update', $product) }}"
        data-inventory-stock-form
        data-product-id="{{ $product->id }}"
        class="flex flex-wrap items-end gap-2"
    >
        @csrf
        @method('PATCH')
        @foreach ($product->sizes as $size)
            <label class="dj-inventory-size-input">
                <span>{{ $size->size }}</span>
                <input type="number" name="sizes[{{ $size->size }}]" value="{{ $size->stock }}" min="0" data-size="{{ $size->size }}">
            </label>
        @endforeach
        <button type="submit" class="dj-admin-btn dj-admin-btn-secondary dj-admin-btn-sm" data-inventory-save-btn>{{ __('general.save') }}</button>
    </form>
@endif
