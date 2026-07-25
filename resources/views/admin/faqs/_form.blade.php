@csrf
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="dj-admin-label">{{ __('faqs.question_en') }}</label>
        <input type="text" name="question_en" value="{{ old('question_en', $faq->question_en ?? '') }}" required maxlength="500" class="dj-admin-input">
        @error('question_en') <p class="dj-admin-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="dj-admin-label">{{ __('faqs.question_ar') }}</label>
        <input type="text" name="question_ar" value="{{ old('question_ar', $faq->question_ar ?? '') }}" required dir="rtl" maxlength="500" class="dj-admin-input">
        @error('question_ar') <p class="dj-admin-error">{{ $message }}</p> @enderror
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
    <div>
        <label class="dj-admin-label">{{ __('faqs.answer_en') }}</label>
        <textarea name="answer_en" required maxlength="5000" rows="4" class="dj-admin-input">{{ old('answer_en', $faq->answer_en ?? '') }}</textarea>
        @error('answer_en') <p class="dj-admin-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="dj-admin-label">{{ __('faqs.answer_ar') }}</label>
        <textarea name="answer_ar" dir="rtl" required maxlength="5000" rows="4" class="dj-admin-input">{{ old('answer_ar', $faq->answer_ar ?? '') }}</textarea>
        @error('answer_ar') <p class="dj-admin-error">{{ $message }}</p> @enderror
    </div>
</div>

<label class="dj-admin-checkbox-row mt-4">
    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $faq->is_active ?? true) ? 'checked' : '' }}>
    {{ __('faqs.is_active') }}
</label>

<button type="submit" class="dj-admin-btn dj-admin-btn-primary mt-4">{{ __('faqs.save_faq') }}</button>
