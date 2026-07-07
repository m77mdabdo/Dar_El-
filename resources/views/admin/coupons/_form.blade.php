@csrf
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium mb-1">Code</label>
        <input type="text" name="code" value="{{ old('code', $coupon->code ?? '') }}" required class="w-full rounded border-stone-300 uppercase">
        @error('code') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">Type</label>
        <select name="type" class="w-full rounded border-stone-300">
            <option value="percentage" @selected(old('type', $coupon->type ?? '') === 'percentage')>Percentage</option>
            <option value="fixed" @selected(old('type', $coupon->type ?? '') === 'fixed')>Fixed Amount</option>
        </select>
    </div>
</div>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium mb-1">Value</label>
        <input type="number" name="value" value="{{ old('value', $coupon->value ?? '') }}" required class="w-full rounded border-stone-300">
        @error('value') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">Min Order Amount</label>
        <input type="number" name="min_order_amount" value="{{ old('min_order_amount', $coupon->min_order_amount ?? 0) }}" class="w-full rounded border-stone-300">
    </div>
</div>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium mb-1">Max Uses (optional)</label>
        <input type="number" name="max_uses" value="{{ old('max_uses', $coupon->max_uses ?? '') }}" class="w-full rounded border-stone-300">
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">Expires At (optional)</label>
        <input type="date" name="expires_at" value="{{ old('expires_at', isset($coupon) ? $coupon->expires_at?->format('Y-m-d') : null) }}" class="w-full rounded border-stone-300">
    </div>
</div>
<label class="flex items-center gap-2 text-sm">
    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $coupon->is_active ?? true) ? 'checked' : '' }}>
    Active
</label>
<button type="submit" class="bg-rose-700 hover:bg-rose-800 text-white px-8 py-3 rounded">Save Coupon</button>
