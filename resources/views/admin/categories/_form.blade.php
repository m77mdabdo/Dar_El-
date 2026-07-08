@csrf
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="dj-admin-label">{{ __('categories.name_en') }}</label>
        <input type="text" name="name_en" value="{{ old('name_en', $category->name_en ?? '') }}" required class="dj-admin-input">
        @error('name_en') <p class="dj-admin-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="dj-admin-label">{{ __('categories.name_ar') }}</label>
        <input type="text" name="name_ar" value="{{ old('name_ar', $category->name_ar ?? '') }}" required dir="rtl" class="dj-admin-input">
        @error('name_ar') <p class="dj-admin-error">{{ $message }}</p> @enderror
    </div>
</div>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="dj-admin-label">{{ __('categories.description_en') }}</label>
        <textarea name="description_en" class="dj-admin-input">{{ old('description_en', $category->description_en ?? '') }}</textarea>
    </div>
    <div>
        <label class="dj-admin-label">{{ __('categories.description_ar') }}</label>
        <textarea name="description_ar" dir="rtl" class="dj-admin-input">{{ old('description_ar', $category->description_ar ?? '') }}</textarea>
    </div>
</div>
<div>
    <label class="dj-admin-label">{{ __('categories.sort_order') }}</label>
    <input type="number" name="sort_order" value="{{ old('sort_order', $category->sort_order ?? 0) }}" class="dj-admin-input w-32">
</div>
<div>
    <label class="dj-admin-label">{{ __('categories.image') }}</label>
    @isset($category)
        @if ($category->image)
            <img src="{{ asset('storage/'.$category->image) }}" class="w-24 h-24 object-cover rounded-lg border border-[var(--dj-cream-2)] mb-2">
        @endif
    @endisset
    <input type="file" name="image" accept="image/*" class="w-full text-sm">
    @error('image') <p class="dj-admin-error">{{ $message }}</p> @enderror
    <p class="dj-admin-hint">{{ __('categories.image_hint') }}</p>
</div>
<label class="dj-admin-checkbox-row">
    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $category->is_active ?? true) ? 'checked' : '' }}>
    {{ __('general.active') }}
</label>
<button type="submit" class="dj-admin-btn dj-admin-btn-primary">{{ __('categories.save_category') }}</button>
