@csrf
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="dj-admin-label">{{ __('blog.title_en') }}</label>
        <input type="text" name="title_en" value="{{ old('title_en', $post->title_en ?? '') }}" required class="dj-admin-input">
        @error('title_en') <p class="dj-admin-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="dj-admin-label">{{ __('blog.title_ar') }}</label>
        <input type="text" name="title_ar" value="{{ old('title_ar', $post->title_ar ?? '') }}" required dir="rtl" class="dj-admin-input">
        @error('title_ar') <p class="dj-admin-error">{{ $message }}</p> @enderror
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="dj-admin-label">{{ __('blog.excerpt_en') }}</label>
        <textarea name="excerpt_en" class="dj-admin-input">{{ old('excerpt_en', $post->excerpt_en ?? '') }}</textarea>
    </div>
    <div>
        <label class="dj-admin-label">{{ __('blog.excerpt_ar') }}</label>
        <textarea name="excerpt_ar" dir="rtl" class="dj-admin-input">{{ old('excerpt_ar', $post->excerpt_ar ?? '') }}</textarea>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="dj-admin-label">{{ __('blog.body_en') }}</label>
        <textarea name="body_en" rows="8" required class="dj-admin-input">{{ old('body_en', $post->body_en ?? '') }}</textarea>
        @error('body_en') <p class="dj-admin-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="dj-admin-label">{{ __('blog.body_ar') }}</label>
        <textarea name="body_ar" dir="rtl" rows="8" required class="dj-admin-input">{{ old('body_ar', $post->body_ar ?? '') }}</textarea>
        @error('body_ar') <p class="dj-admin-error">{{ $message }}</p> @enderror
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="dj-admin-label">{{ __('blog.published_at') }}</label>
        <input type="datetime-local" name="published_at" value="{{ old('published_at', isset($post) ? $post->published_at?->format('Y-m-d\TH:i') : '') }}" class="dj-admin-input">
    </div>
    <label class="dj-admin-checkbox-row mt-6">
        <input type="checkbox" name="is_published" value="1" {{ old('is_published', $post->is_published ?? false) ? 'checked' : '' }}>
        {{ __('general.published') }}
    </label>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="dj-admin-label">{{ __('product_options.meta_title_en') }}</label>
        <input type="text" name="meta_title_en" value="{{ old('meta_title_en', $post->meta_title_en ?? '') }}" maxlength="255" class="dj-admin-input">
        @error('meta_title_en') <p class="dj-admin-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="dj-admin-label">{{ __('product_options.meta_title_ar') }}</label>
        <input type="text" name="meta_title_ar" value="{{ old('meta_title_ar', $post->meta_title_ar ?? '') }}" dir="rtl" maxlength="255" class="dj-admin-input">
        @error('meta_title_ar') <p class="dj-admin-error">{{ $message }}</p> @enderror
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="dj-admin-label">{{ __('product_options.meta_description_en') }}</label>
        <textarea name="meta_description_en" maxlength="500" rows="3" class="dj-admin-input">{{ old('meta_description_en', $post->meta_description_en ?? '') }}</textarea>
        @error('meta_description_en') <p class="dj-admin-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="dj-admin-label">{{ __('product_options.meta_description_ar') }}</label>
        <textarea name="meta_description_ar" dir="rtl" maxlength="500" rows="3" class="dj-admin-input">{{ old('meta_description_ar', $post->meta_description_ar ?? '') }}</textarea>
        @error('meta_description_ar') <p class="dj-admin-error">{{ $message }}</p> @enderror
    </div>
</div>
<p class="dj-admin-hint">{{ __('product_options.seo_hint') }}</p>

<div>
    <label class="dj-admin-label">{{ __('blog.cover_image') }}</label>
    @isset($post)
        @if ($post->cover_image)
            <img src="{{ asset('storage/'.$post->cover_image) }}" class="w-32 h-20 object-cover rounded-lg border border-[var(--dj-cream-2)] mb-2">
        @endif
    @endisset
    <input type="file" name="cover_image" accept="image/*" class="w-full text-sm">
    @error('cover_image') <p class="dj-admin-error">{{ $message }}</p> @enderror
    <p class="dj-admin-hint">{{ __('blog.cover_image_hint') }}</p>
</div>

<button type="submit" class="dj-admin-btn dj-admin-btn-primary">{{ __('blog.save_post') }}</button>
