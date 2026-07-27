@csrf
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="dj-admin-label">{{ __('services.title_en') }}</label>
        <input type="text" name="title_en" value="{{ old('title_en', $service->title_en ?? '') }}" required maxlength="255" class="dj-admin-input">
        @error('title_en') <p class="dj-admin-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="dj-admin-label">{{ __('services.title_ar') }}</label>
        <input type="text" name="title_ar" value="{{ old('title_ar', $service->title_ar ?? '') }}" required dir="rtl" maxlength="255" class="dj-admin-input">
        @error('title_ar') <p class="dj-admin-error">{{ $message }}</p> @enderror
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
    <div>
        <label class="dj-admin-label">{{ __('services.description_en') }}</label>
        <textarea name="description_en" required maxlength="1000" rows="3" class="dj-admin-input">{{ old('description_en', $service->description_en ?? '') }}</textarea>
        @error('description_en') <p class="dj-admin-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="dj-admin-label">{{ __('services.description_ar') }}</label>
        <textarea name="description_ar" dir="rtl" required maxlength="1000" rows="3" class="dj-admin-input">{{ old('description_ar', $service->description_ar ?? '') }}</textarea>
        @error('description_ar') <p class="dj-admin-error">{{ $message }}</p> @enderror
    </div>
</div>

<div class="mt-4 flex items-end gap-4">
    <div class="flex-1">
        <label class="dj-admin-label">{{ __('services.icon') }}</label>
        <select name="icon" class="dj-admin-input">
            @php $djSelectedIcon = old('icon', $service->icon ?? 'star'); @endphp
            @foreach (\App\Models\Service::ICONS as $djIconKey => $djIconSvg)
                <option value="{{ $djIconKey }}" {{ $djSelectedIcon === $djIconKey ? 'selected' : '' }}>{{ __('services.icon_'.$djIconKey) }}</option>
            @endforeach
        </select>
        @error('icon') <p class="dj-admin-error">{{ $message }}</p> @enderror
    </div>
    <div class="w-[58px] h-[58px] rounded-2xl shrink-0 flex items-center justify-center" style="background:linear-gradient(135deg, var(--dj-maroon), var(--dj-maroon-soft));">
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="var(--dj-gold)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">{!! \App\Models\Service::ICONS[$djSelectedIcon] ?? \App\Models\Service::ICONS['star'] !!}</svg>
    </div>
</div>

<label class="dj-admin-checkbox-row mt-4">
    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $service->is_active ?? true) ? 'checked' : '' }}>
    {{ __('services.is_active') }}
</label>

<button type="submit" class="dj-admin-btn dj-admin-btn-primary mt-4">{{ __('services.save_service') }}</button>
