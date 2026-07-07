@csrf
<div>
    <label class="block text-sm font-medium mb-1">{{ __('Label (e.g. Home, Work)') }}</label>
    <input type="text" name="label" value="{{ old('label', $address->label ?? '') }}" class="w-full rounded border-stone-300">
</div>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium mb-1">{{ __('First Name') }}</label>
        <input type="text" name="first_name" value="{{ old('first_name', $address->first_name ?? '') }}" required class="w-full rounded border-stone-300">
        @error('first_name') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">{{ __('Last Name') }}</label>
        <input type="text" name="last_name" value="{{ old('last_name', $address->last_name ?? '') }}" required class="w-full rounded border-stone-300">
        @error('last_name') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
</div>
<div>
    <label class="block text-sm font-medium mb-1">{{ __('Phone') }}</label>
    <input type="text" name="phone" value="{{ old('phone', $address->phone ?? '') }}" required class="w-full rounded border-stone-300">
    @error('phone') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
</div>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium mb-1">{{ __('Governorate') }}</label>
        <input type="text" name="governorate" value="{{ old('governorate', $address->governorate ?? '') }}" required class="w-full rounded border-stone-300">
        @error('governorate') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">{{ __('City') }}</label>
        <input type="text" name="city" value="{{ old('city', $address->city ?? '') }}" required class="w-full rounded border-stone-300">
        @error('city') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
</div>
<div>
    <label class="block text-sm font-medium mb-1">{{ __('Address') }}</label>
    <textarea name="address" required class="w-full rounded border-stone-300">{{ old('address', $address->address ?? '') }}</textarea>
    @error('address') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
</div>
<label class="flex items-center gap-2 text-sm">
    <input type="checkbox" name="is_default" value="1" {{ old('is_default', $address->is_default ?? false) ? 'checked' : '' }}>
    {{ __('Set as default address') }}
</label>

<button type="submit" class="bg-rose-700 hover:bg-rose-800 text-white px-8 py-3 rounded">{{ __('Save Address') }}</button>
