@csrf
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="dj-admin-label">{{ __('shipping_methods.name_en') }}</label>
        <input type="text" name="name_en" value="{{ old('name_en', $shippingMethod->name_en ?? '') }}" required class="dj-admin-input">
        @error('name_en') <p class="dj-admin-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="dj-admin-label">{{ __('shipping_methods.name_ar') }}</label>
        <input type="text" name="name_ar" value="{{ old('name_ar', $shippingMethod->name_ar ?? '') }}" required dir="rtl" class="dj-admin-input">
        @error('name_ar') <p class="dj-admin-error">{{ $message }}</p> @enderror
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
    <div>
        <label class="dj-admin-label">{{ __('shipping_methods.description_en') }}</label>
        <textarea name="description_en" class="dj-admin-input">{{ old('description_en', $shippingMethod->description_en ?? '') }}</textarea>
    </div>
    <div>
        <label class="dj-admin-label">{{ __('shipping_methods.description_ar') }}</label>
        <textarea name="description_ar" dir="rtl" class="dj-admin-input">{{ old('description_ar', $shippingMethod->description_ar ?? '') }}</textarea>
    </div>
</div>

<div class="mt-4">
    <label class="dj-admin-label">{{ __('shipping_methods.code') }}</label>
    <input type="text" name="code" value="{{ old('code', $shippingMethod->code ?? '') }}" placeholder="standard" class="dj-admin-input" style="max-width:220px;">
    @error('code') <p class="dj-admin-error">{{ $message }}</p> @enderror
    <p class="dj-admin-hint">{{ __('shipping_methods.code_hint') }}</p>
</div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
    <div>
        <label class="dj-admin-label">{{ __('shipping_methods.fee') }}</label>
        <input type="number" name="fee" value="{{ old('fee', $shippingMethod->fee ?? 0) }}" min="0" required class="dj-admin-input">
        @error('fee') <p class="dj-admin-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="dj-admin-label">{{ __('shipping_methods.delivery_time_min_days') }}</label>
        <input type="number" name="delivery_time_min_days" value="{{ old('delivery_time_min_days', $shippingMethod->delivery_time_min_days ?? 3) }}" min="0" required class="dj-admin-input">
        @error('delivery_time_min_days') <p class="dj-admin-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="dj-admin-label">{{ __('shipping_methods.delivery_time_max_days') }}</label>
        <input type="number" name="delivery_time_max_days" value="{{ old('delivery_time_max_days', $shippingMethod->delivery_time_max_days ?? 5) }}" min="0" required class="dj-admin-input">
        @error('delivery_time_max_days') <p class="dj-admin-error">{{ $message }}</p> @enderror
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4 items-end">
    <div>
        <label class="dj-admin-label">{{ __('shipping_methods.sort_order') }}</label>
        <input type="number" name="sort_order" value="{{ old('sort_order', $shippingMethod->sort_order ?? 0) }}" class="dj-admin-input">
        @error('sort_order') <p class="dj-admin-error">{{ $message }}</p> @enderror
    </div>
    <label class="dj-admin-checkbox-row">
        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $shippingMethod->is_active ?? true) ? 'checked' : '' }}>
        {{ __('shipping_methods.is_active') }}
    </label>
</div>

<button type="submit" class="dj-admin-btn dj-admin-btn-primary mt-4">{{ __('shipping_methods.save_shipping_method') }}</button>
