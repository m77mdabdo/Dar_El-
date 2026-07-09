<div
    x-data="{
        titleAr: '{{ $product->meta_title_ar ?? $product->name_ar }}',
        titleEn: '{{ $product->meta_title_en ?? $product->name_en }}',
        descAr: {{ Illuminate\Support\Js::from($product->meta_description_ar ?? $product->description_ar ?? '') }},
        descEn: {{ Illuminate\Support\Js::from($product->meta_description_en ?? $product->description_en ?? '') }},
    }"
    class="space-y-6"
>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="dj-admin-label">{{ __('product_options.meta_title_en') }}</label>
            <input type="text" name="meta_title_en" x-model="titleEn" maxlength="255" class="dj-admin-input">
            @error('meta_title_en') <p class="dj-admin-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="dj-admin-label">{{ __('product_options.meta_title_ar') }}</label>
            <input type="text" name="meta_title_ar" x-model="titleAr" dir="rtl" maxlength="255" class="dj-admin-input">
            @error('meta_title_ar') <p class="dj-admin-error">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="dj-admin-label">{{ __('product_options.meta_description_en') }}</label>
            <textarea name="meta_description_en" x-model="descEn" maxlength="500" rows="3" class="dj-admin-input"></textarea>
            @error('meta_description_en') <p class="dj-admin-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="dj-admin-label">{{ __('product_options.meta_description_ar') }}</label>
            <textarea name="meta_description_ar" x-model="descAr" dir="rtl" maxlength="500" rows="3" class="dj-admin-input"></textarea>
            @error('meta_description_ar') <p class="dj-admin-error">{{ $message }}</p> @enderror
        </div>
    </div>

    <p class="dj-admin-hint">{{ __('product_options.seo_hint') }}</p>

    <div>
        <p class="dj-admin-label mb-2">{{ __('product_options.seo_preview') }}</p>
        <div class="rounded-lg border border-[var(--dj-cream-2)] p-4 bg-white max-w-xl" dir="ltr">
            <p class="text-[#1a0dab] text-lg leading-snug truncate" x-text="titleEn"></p>
            <p class="text-[#006621] text-sm">{{ url('/shop/'.$product->slug) }}</p>
            <p class="text-[#545454] text-sm line-clamp-2" x-text="descEn"></p>
        </div>
    </div>
</div>
