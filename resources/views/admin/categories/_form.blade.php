@csrf
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium mb-1">Name (English)</label>
        <input type="text" name="name_en" value="{{ old('name_en', $category->name_en ?? '') }}" required class="w-full rounded border-stone-300">
        @error('name_en') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">Name (Arabic)</label>
        <input type="text" name="name_ar" value="{{ old('name_ar', $category->name_ar ?? '') }}" required dir="rtl" class="w-full rounded border-stone-300">
        @error('name_ar') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
</div>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium mb-1">Description (English)</label>
        <textarea name="description_en" class="w-full rounded border-stone-300">{{ old('description_en', $category->description_en ?? '') }}</textarea>
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">Description (Arabic)</label>
        <textarea name="description_ar" dir="rtl" class="w-full rounded border-stone-300">{{ old('description_ar', $category->description_ar ?? '') }}</textarea>
    </div>
</div>
<div>
    <label class="block text-sm font-medium mb-1">Sort Order</label>
    <input type="number" name="sort_order" value="{{ old('sort_order', $category->sort_order ?? 0) }}" class="w-32 rounded border-stone-300">
</div>
<div>
    <label class="block text-sm font-medium mb-2">Image</label>
    @isset($category)
        @if ($category->image)
            <img src="{{ asset('storage/'.$category->image) }}" class="w-24 h-24 object-cover rounded border border-stone-200 mb-2">
        @endif
    @endisset
    <input type="file" name="image" accept="image/*" class="w-full text-sm">
    @error('image') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
    <p class="text-xs text-stone-500 mt-1">JPG, PNG, or WEBP. Max 4MB. Uploading a new image replaces the current one.</p>
</div>
<label class="flex items-center gap-2 text-sm">
    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $category->is_active ?? true) ? 'checked' : '' }}>
    Active
</label>
<button type="submit" class="bg-rose-700 hover:bg-rose-800 text-white px-8 py-3 rounded">Save Category</button>
