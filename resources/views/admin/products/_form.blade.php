@csrf
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="dj-admin-label">{{ __('products.name_en') }}</label>
        <input type="text" name="name_en" value="{{ old('name_en', $product->name_en ?? '') }}" required class="dj-admin-input">
        @error('name_en') <p class="dj-admin-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="dj-admin-label">{{ __('products.name_ar') }}</label>
        <input type="text" name="name_ar" value="{{ old('name_ar', $product->name_ar ?? '') }}" required dir="rtl" class="dj-admin-input">
        @error('name_ar') <p class="dj-admin-error">{{ $message }}</p> @enderror
    </div>
</div>

<div>
    <label class="dj-admin-label">{{ __('products.category') }}</label>
    <select name="category_id" required class="dj-admin-input">
        @foreach ($categories as $category)
            <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id ?? null) == $category->id)>{{ trans_field($category, 'name') }}</option>
        @endforeach
    </select>
    @error('category_id') <p class="dj-admin-error">{{ $message }}</p> @enderror
</div>

<div>
    <label class="dj-admin-label">{{ __('products.cover_photo') }}</label>
    @isset($product)
        @if ($product->cover_image_src)
            <img src="{{ $product->cover_image_src }}" class="w-24 h-24 object-cover rounded-lg border border-[var(--dj-cream-2)] mb-2">
        @endif
    @endisset
    <input type="file" name="image_url" accept="image/*" class="w-full text-sm">
    @error('image_url') <p class="dj-admin-error">{{ $message }}</p> @enderror
    <p class="dj-admin-hint">{{ __('products.cover_photo_hint') }}</p>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="dj-admin-label">{{ __('products.description_en') }}</label>
        <textarea name="description_en" class="dj-admin-input">{{ old('description_en', $product->description_en ?? '') }}</textarea>
    </div>
    <div>
        <label class="dj-admin-label">{{ __('products.description_ar') }}</label>
        <textarea name="description_ar" dir="rtl" class="dj-admin-input">{{ old('description_ar', $product->description_ar ?? '') }}</textarea>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div>
        <label class="dj-admin-label">{{ __('products.price') }} (EGP)</label>
        <input type="number" name="price" value="{{ old('price', $product->price ?? '') }}" required class="dj-admin-input">
        @error('price') <p class="dj-admin-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="dj-admin-label">{{ __('products.compare_at_price') }}</label>
        <input type="number" name="compare_at_price" value="{{ old('compare_at_price', $product->compare_at_price ?? '') }}" class="dj-admin-input">
    </div>
    <div>
        <label class="dj-admin-label">{{ __('products.sku') }}</label>
        <input type="text" name="sku" value="{{ old('sku', $product->sku ?? '') }}" class="dj-admin-input">
    </div>
</div>

<div>
    <label class="dj-admin-label">{{ __('products.badge') }}</label>
    <input type="text" name="badge" value="{{ old('badge', $product->badge ?? '') }}" placeholder="{{ __('products.badge_placeholder') }}" class="dj-admin-input">
</div>

<div>
    <label class="dj-admin-label">{{ __('products.offer_ends_at') }}</label>
    <input type="datetime-local" name="offer_ends_at" value="{{ old('offer_ends_at', isset($product) ? $product->offer_ends_at?->format('Y-m-d\TH:i') : '') }}" class="dj-admin-input">
    @error('offer_ends_at') <p class="dj-admin-error">{{ $message }}</p> @enderror
    <p class="dj-admin-hint">{{ __('products.offer_ends_at_hint') }}</p>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4" x-data="{ status: '{{ old('status', $product->status ?? 'published') }}' }">
    <div>
        <label class="dj-admin-label">{{ __('products.status') }}</label>
        <select name="status" x-model="status" class="dj-admin-input">
            <option value="draft">{{ __('products.status_draft') }}</option>
            <option value="scheduled">{{ __('products.status_scheduled') }}</option>
            <option value="published">{{ __('products.status_published') }}</option>
            <option value="archived">{{ __('products.status_archived') }}</option>
        </select>
        @error('status') <p class="dj-admin-error">{{ $message }}</p> @enderror
    </div>
    <div x-show="status === 'scheduled'" x-cloak>
        <label class="dj-admin-label">{{ __('products.scheduled_publish_at') }}</label>
        <input type="datetime-local" name="scheduled_publish_at" value="{{ old('scheduled_publish_at', isset($product) ? $product->scheduled_publish_at?->format('Y-m-d\TH:i') : '') }}" class="dj-admin-input">
        @error('scheduled_publish_at') <p class="dj-admin-error">{{ $message }}</p> @enderror
    </div>
</div>

<div class="flex gap-6">
    <label class="dj-admin-checkbox-row">
        <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $product->is_featured ?? false) ? 'checked' : '' }}>
        {{ __('products.featured') }}
    </label>
</div>

<div class="dj-admin-card p-4 mt-2">
    <label for="dj-related-select" class="font-semibold text-[var(--dj-maroon-dark)] block">{{ __('products.related_products_heading') }}</label>
    <p class="dj-admin-hint mb-3">{{ __('products.related_products_hint') }}</p>

    @php
        $djSelectedRelatedIds = old('related_product_ids', isset($product) ? $product->relatedProducts->pluck('id')->all() : []);
    @endphp

    <label for="dj-related-filter" class="sr-only">{{ __('products.related_products_search_placeholder') }}</label>
    <input type="text" id="dj-related-filter" placeholder="{{ __('products.related_products_search_placeholder') }}" class="dj-admin-input mb-2">
    <select name="related_product_ids[]" id="dj-related-select" multiple size="8" class="dj-admin-input" style="height:200px;">
        @foreach ($relatableProducts as $candidate)
            <option value="{{ $candidate->id }}" data-search="{{ \Illuminate\Support\Str::lower($candidate->name_ar.' '.$candidate->name_en) }}" @selected(in_array($candidate->id, $djSelectedRelatedIds))>{{ $candidate->name_ar }} — {{ $candidate->name_en }}</option>
        @endforeach
    </select>
    @error('related_product_ids') <p class="dj-admin-error">{{ $message }}</p> @enderror
    <p class="dj-admin-hint mt-1">{{ __('products.related_products_multiselect_hint') }}</p>
</div>

<script>
    (function () {
        var filterInput = document.getElementById('dj-related-filter');
        var select = document.getElementById('dj-related-select');
        if (! filterInput || ! select) return;

        filterInput.addEventListener('input', function () {
            var term = this.value.trim().toLowerCase();
            select.querySelectorAll('option').forEach(function (option) {
                option.hidden = term !== '' && option.dataset.search.indexOf(term) === -1;
            });
        });
    })();
</script>

@isset($product)
    <div class="dj-admin-card p-4 mt-2">
        <p class="font-semibold text-[var(--dj-maroon-dark)]">{{ __('product_options.smart_defaults_heading') }}</p>
        <p class="dj-admin-hint mb-3">{{ __('product_options.smart_defaults_hint') }}</p>
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <div>
                <label class="dj-admin-label">{{ __('product_options.sku_prefix') }}</label>
                <input type="text" name="sku_prefix" value="{{ old('sku_prefix', $product->sku_prefix) }}" placeholder="{{ __('product_options.sku_prefix_placeholder') }}" class="dj-admin-input">
                @error('sku_prefix') <p class="dj-admin-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="dj-admin-label">{{ __('product_options.default_stock') }}</label>
                <input type="number" name="default_stock" value="{{ old('default_stock', $product->default_stock) }}" min="0" class="dj-admin-input">
                @error('default_stock') <p class="dj-admin-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="dj-admin-label">{{ __('product_options.default_low_stock_threshold') }}</label>
                <input type="number" name="default_low_stock_threshold" value="{{ old('default_low_stock_threshold', $product->default_low_stock_threshold) }}" min="0" placeholder="{{ \App\Models\Product::LOW_STOCK_THRESHOLD }}" class="dj-admin-input">
                @error('default_low_stock_threshold') <p class="dj-admin-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="dj-admin-label">{{ __('product_options.weight') }}</label>
                <input type="number" step="0.01" name="weight" value="{{ old('weight', $product->weight) }}" min="0" class="dj-admin-input">
                @error('weight') <p class="dj-admin-error">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>
@endisset
