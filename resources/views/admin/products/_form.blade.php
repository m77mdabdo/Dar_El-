@csrf
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium mb-1">Name (English)</label>
        <input type="text" name="name_en" value="{{ old('name_en', $product->name_en ?? '') }}" required class="w-full rounded border-stone-300">
        @error('name_en') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">Name (Arabic)</label>
        <input type="text" name="name_ar" value="{{ old('name_ar', $product->name_ar ?? '') }}" required dir="rtl" class="w-full rounded border-stone-300">
        @error('name_ar') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
</div>

<div>
    <label class="block text-sm font-medium mb-1">Category</label>
    <select name="category_id" required class="w-full rounded border-stone-300">
        @foreach ($categories as $category)
            <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id ?? null) == $category->id)>{{ $category->name_en }}</option>
        @endforeach
    </select>
    @error('category_id') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
</div>

<div>
    <label class="block text-sm font-medium mb-2">Cover Photo</label>
    @isset($product)
        @if ($product->cover_image_src)
            <img src="{{ $product->cover_image_src }}" class="w-24 h-24 object-cover rounded border border-stone-200 mb-2">
        @endif
    @endisset
    <input type="file" name="image_url" accept="image/*" class="w-full text-sm">
    @error('image_url') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
    <p class="text-xs text-stone-500 mt-1">JPG, PNG, or WEBP. Max 4MB. Uploading a new image replaces the current one. Shown on product cards, the product page, and the cart.</p>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium mb-1">Description (English)</label>
        <textarea name="description_en" class="w-full rounded border-stone-300">{{ old('description_en', $product->description_en ?? '') }}</textarea>
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">Description (Arabic)</label>
        <textarea name="description_ar" dir="rtl" class="w-full rounded border-stone-300">{{ old('description_ar', $product->description_ar ?? '') }}</textarea>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div>
        <label class="block text-sm font-medium mb-1">Price (EGP)</label>
        <input type="number" name="price" value="{{ old('price', $product->price ?? '') }}" required class="w-full rounded border-stone-300">
        @error('price') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">Compare-at Price</label>
        <input type="number" name="compare_at_price" value="{{ old('compare_at_price', $product->compare_at_price ?? '') }}" class="w-full rounded border-stone-300">
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">SKU</label>
        <input type="text" name="sku" value="{{ old('sku', $product->sku ?? '') }}" class="w-full rounded border-stone-300">
    </div>
</div>

<div>
    <label class="block text-sm font-medium mb-1">Badge</label>
    <input type="text" name="badge" value="{{ old('badge', $product->badge ?? '') }}" placeholder="new, bestseller..." class="w-full rounded border-stone-300">
</div>

<div class="flex gap-6">
    <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->is_active ?? true) ? 'checked' : '' }}>
        Active
    </label>
    <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $product->is_featured ?? false) ? 'checked' : '' }}>
        Featured
    </label>
</div>

<div>
    <label class="block text-sm font-medium mb-2">Sizes &amp; Stock</label>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        @php
            $existingSizes = isset($product) ? $product->sizes->pluck('stock', 'size') : collect();
            $sizeOptions = $existingSizes->keys()->count() ? $existingSizes->keys() : collect(['S', 'M', 'L', 'XL']);
        @endphp
        @foreach ($sizeOptions as $size)
            <div>
                <label class="block text-xs text-stone-500 mb-1">{{ $size }}</label>
                <input type="number" name="sizes[{{ $size }}]" value="{{ $existingSizes[$size] ?? 0 }}" min="0" class="w-full rounded border-stone-300 text-sm">
            </div>
        @endforeach
    </div>
</div>

<div>
    <label class="block text-sm font-medium mb-2">Gallery Images</label>
    <input type="file" name="images[]" multiple accept="image/*" class="w-full text-sm">
    @error('images') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
    @foreach ($errors->get('images.*') as $messages)
        @foreach ($messages as $message)
            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
        @endforeach
    @endforeach
    <p class="text-xs text-stone-500 mt-1">JPG, PNG, or WEBP. Max 4MB each.</p>
</div>

<button type="submit" class="bg-rose-700 hover:bg-rose-800 text-white px-8 py-3 rounded">Save Product</button>
