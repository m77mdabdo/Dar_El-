@csrf
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="dj-admin-label">{{ __('hero_banners.title_en') }}</label>
        <input type="text" name="title_en" value="{{ old('title_en', $banner->title_en ?? '') }}" required class="dj-admin-input">
        @error('title_en') <p class="dj-admin-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="dj-admin-label">{{ __('hero_banners.title_ar') }}</label>
        <input type="text" name="title_ar" value="{{ old('title_ar', $banner->title_ar ?? '') }}" required dir="rtl" class="dj-admin-input">
        @error('title_ar') <p class="dj-admin-error">{{ $message }}</p> @enderror
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
    <div>
        <label class="dj-admin-label">{{ __('hero_banners.subtitle_en') }}</label>
        <textarea name="subtitle_en" maxlength="500" rows="2" class="dj-admin-input">{{ old('subtitle_en', $banner->subtitle_en ?? '') }}</textarea>
        @error('subtitle_en') <p class="dj-admin-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="dj-admin-label">{{ __('hero_banners.subtitle_ar') }}</label>
        <textarea name="subtitle_ar" dir="rtl" maxlength="500" rows="2" class="dj-admin-input">{{ old('subtitle_ar', $banner->subtitle_ar ?? '') }}</textarea>
        @error('subtitle_ar') <p class="dj-admin-error">{{ $message }}</p> @enderror
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
    <div>
        <label class="dj-admin-label">{{ __('hero_banners.cta_text_en') }}</label>
        <input type="text" name="cta_text_en" value="{{ old('cta_text_en', $banner->cta_text_en ?? '') }}" maxlength="100" class="dj-admin-input">
        @error('cta_text_en') <p class="dj-admin-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="dj-admin-label">{{ __('hero_banners.cta_text_ar') }}</label>
        <input type="text" name="cta_text_ar" value="{{ old('cta_text_ar', $banner->cta_text_ar ?? '') }}" dir="rtl" maxlength="100" class="dj-admin-input">
        @error('cta_text_ar') <p class="dj-admin-error">{{ $message }}</p> @enderror
    </div>
</div>
<p class="dj-admin-hint">{{ __('hero_banners.cta_text_hint') }}</p>

<div class="mt-4">
    <label class="dj-admin-label">{{ __('hero_banners.link_url') }}</label>
    <input type="text" name="link_url" value="{{ old('link_url', $banner->link_url ?? '') }}" placeholder="/shop" class="dj-admin-input">
    @error('link_url') <p class="dj-admin-error">{{ $message }}</p> @enderror
    <p class="dj-admin-hint">{{ __('hero_banners.link_url_hint') }}</p>
</div>

<div class="mt-4">
    <label class="dj-admin-label">{{ __('hero_banners.image') }}</label>
    @isset($banner)
        @if ($banner->image)
            <img src="{{ $banner->image_thumb }}" class="w-40 h-24 object-cover rounded-lg border border-[var(--dj-cream-2)] mb-2">
        @endif
    @endisset
    <input type="file" name="image" accept="image/*" class="w-full text-sm">
    @error('image') <p class="dj-admin-error">{{ $message }}</p> @enderror
    <p class="dj-admin-hint">{{ __('categories.image_hint') }}</p>
</div>

<label class="dj-admin-checkbox-row mt-4">
    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $banner->is_active ?? true) ? 'checked' : '' }}>
    {{ __('hero_banners.is_active') }}
</label>

<button type="submit" class="dj-admin-btn dj-admin-btn-primary mt-4">{{ __('hero_banners.save_hero_banner') }}</button>
