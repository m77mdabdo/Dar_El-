@csrf
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium mb-1">Title (English)</label>
        <input type="text" name="title_en" value="{{ old('title_en', $post->title_en ?? '') }}" required class="w-full rounded border-stone-300">
        @error('title_en') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">Title (Arabic)</label>
        <input type="text" name="title_ar" value="{{ old('title_ar', $post->title_ar ?? '') }}" required dir="rtl" class="w-full rounded border-stone-300">
        @error('title_ar') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium mb-1">Excerpt (English)</label>
        <textarea name="excerpt_en" class="w-full rounded border-stone-300">{{ old('excerpt_en', $post->excerpt_en ?? '') }}</textarea>
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">Excerpt (Arabic)</label>
        <textarea name="excerpt_ar" dir="rtl" class="w-full rounded border-stone-300">{{ old('excerpt_ar', $post->excerpt_ar ?? '') }}</textarea>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium mb-1">Body (English)</label>
        <textarea name="body_en" rows="8" required class="w-full rounded border-stone-300">{{ old('body_en', $post->body_en ?? '') }}</textarea>
        @error('body_en') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">Body (Arabic)</label>
        <textarea name="body_ar" dir="rtl" rows="8" required class="w-full rounded border-stone-300">{{ old('body_ar', $post->body_ar ?? '') }}</textarea>
        @error('body_ar') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium mb-1">Published At</label>
        <input type="datetime-local" name="published_at" value="{{ old('published_at', isset($post) ? $post->published_at?->format('Y-m-d\TH:i') : '') }}" class="w-full rounded border-stone-300">
    </div>
    <label class="flex items-center gap-2 text-sm mt-6">
        <input type="checkbox" name="is_published" value="1" {{ old('is_published', $post->is_published ?? false) ? 'checked' : '' }}>
        Published
    </label>
</div>

<div>
    <label class="block text-sm font-medium mb-2">Cover Image</label>
    @isset($post)
        @if ($post->cover_image)
            <img src="{{ asset('storage/'.$post->cover_image) }}" class="w-32 h-20 object-cover rounded border border-stone-200 mb-2">
        @endif
    @endisset
    <input type="file" name="cover_image" accept="image/*" class="w-full text-sm">
    @error('cover_image') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
    <p class="text-xs text-stone-500 mt-1">JPG, PNG, or WEBP. Max 4MB. Uploading a new image replaces the current one.</p>
</div>

<button type="submit" class="bg-rose-700 hover:bg-rose-800 text-white px-8 py-3 rounded">Save Post</button>
