@csrf
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="dj-admin-label">{{ __('coupons.code') }}</label>
        <input type="text" name="code" value="{{ old('code', $coupon->code ?? '') }}" required class="dj-admin-input uppercase">
        @error('code') <p class="dj-admin-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="dj-admin-label">{{ __('coupons.type') }}</label>
        <select name="type" class="dj-admin-input">
            <option value="percentage" @selected(old('type', $coupon->type ?? '') === 'percentage')>{{ __('coupons.percentage') }}</option>
            <option value="fixed" @selected(old('type', $coupon->type ?? '') === 'fixed')>{{ __('coupons.fixed') }}</option>
        </select>
    </div>
</div>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="dj-admin-label">{{ __('coupons.value') }}</label>
        <input type="number" name="value" value="{{ old('value', $coupon->value ?? '') }}" required class="dj-admin-input">
        @error('value') <p class="dj-admin-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="dj-admin-label">{{ __('coupons.min_order_amount') }}</label>
        <input type="number" name="min_order_amount" value="{{ old('min_order_amount', $coupon->min_order_amount ?? 0) }}" class="dj-admin-input">
    </div>
</div>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="dj-admin-label">{{ __('coupons.max_uses') }}</label>
        <input type="number" name="max_uses" value="{{ old('max_uses', $coupon->max_uses ?? '') }}" class="dj-admin-input">
    </div>
    <div>
        <label class="dj-admin-label">{{ __('coupons.expires_at') }}</label>
        <input type="date" name="expires_at" value="{{ old('expires_at', isset($coupon) ? $coupon->expires_at?->format('Y-m-d') : null) }}" class="dj-admin-input">
    </div>
</div>
<label class="dj-admin-checkbox-row">
    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $coupon->is_active ?? true) ? 'checked' : '' }}>
    {{ __('general.active') }}
</label>
<button type="submit" class="dj-admin-btn dj-admin-btn-primary">{{ __('coupons.save_coupon') }}</button>
