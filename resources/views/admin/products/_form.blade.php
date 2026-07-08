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
            <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id ?? null) == $category->id)>{{ $category->name_en }}</option>
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

<div class="flex gap-6">
    <label class="dj-admin-checkbox-row">
        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->is_active ?? true) ? 'checked' : '' }}>
        {{ __('general.active') }}
    </label>
    <label class="dj-admin-checkbox-row">
        <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $product->is_featured ?? false) ? 'checked' : '' }}>
        {{ __('products.featured') }}
    </label>
</div>

<div>
    <label class="dj-admin-label">{{ __('products.sizes_stock') }}</label>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        @php
            $existingSizes = isset($product) ? $product->sizes->pluck('stock', 'size') : collect();
            $sizeOptions = $existingSizes->keys()->count() ? $existingSizes->keys() : collect(['S', 'M', 'L', 'XL']);
        @endphp
        @foreach ($sizeOptions as $size)
            <div>
                <label class="dj-admin-label text-[11px]">{{ $size }}</label>
                <input type="number" name="sizes[{{ $size }}]" value="{{ $existingSizes[$size] ?? 0 }}" min="0" class="dj-admin-input">
            </div>
        @endforeach
    </div>
</div>

<div>
    <label class="dj-admin-label">{{ __('products.gallery_images') }}</label>
    <input type="file" name="images[]" multiple accept="image/*" class="w-full text-sm">
    @error('images') <p class="dj-admin-error">{{ $message }}</p> @enderror
    @foreach ($errors->get('images.*') as $messages)
        @foreach ($messages as $message)
            <p class="dj-admin-error">{{ $message }}</p>
        @endforeach
    @endforeach
    <p class="dj-admin-hint">{{ __('products.gallery_images_hint') }}</p>
</div>

<button type="submit" class="dj-admin-btn dj-admin-btn-primary">{{ __('products.save_product') }}</button>
