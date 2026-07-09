@extends('admin.layout')

@section('title', __('products.edit_product'))

@section('content')
    <div
        class="max-w-4xl"
        x-data="djProductWizard(
            {{ $wizard ? 'true' : 'false' }},
            '{{ route('admin.products.bulk-action') }}',
            {{ $product->id }},
            '{{ route('admin.products.index') }}'
        )"
        data-toast-published="{{ __('product_options.publish_success') }}"
        data-toast-error="{{ __('product_options.bulk_action_error') }}"
    >
        <div class="flex items-center justify-between mb-2 gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <button type="button" class="dj-admin-link-muted text-xs shrink-0" @click="wizardMode = ! wizardMode">
                    <span x-text="wizardMode ? '{{ __('product_options.switch_to_classic') }}' : '{{ __('product_options.switch_to_wizard') }}'"></span>
                </button>
                <span class="dj-admin-hint hidden sm:inline truncate">{{ __('product_options.shortcuts_hint') }}</span>
            </div>
            <span
                data-autosave-indicator
                data-idle-label=""
                data-unsaved-label="{{ __('product_options.autosave_unsaved') }}"
                data-saving-label="{{ __('product_options.autosave_saving') }}"
                data-saved-label="{{ __('product_options.autosave_saved') }}"
                data-error-label="{{ __('product_options.autosave_error') }}"
                class="dj-admin-hint shrink-0 cursor-default"
            ></span>
        </div>

        <div class="dj-admin-tabs flex items-center justify-between" x-show="!wizardMode">
            <div class="flex flex-wrap">
                <button type="button" @click="tab = 'basic'" :class="{ 'dj-active': tab === 'basic' }" class="dj-admin-tab">{{ __('product_options.tab_basic_info') }}</button>
                <button type="button" @click="tab = 'options'" :class="{ 'dj-active': tab === 'options' }" class="dj-admin-tab">{{ __('product_options.tab_options') }}</button>
                <button type="button" @click="tab = 'variants'" :class="{ 'dj-active': tab === 'variants' }" class="dj-admin-tab">{{ __('product_options.tab_variants') }}</button>
                <button type="button" @click="tab = 'images'" :class="{ 'dj-active': tab === 'images' }" class="dj-admin-tab">{{ __('product_options.tab_images') }}</button>
                <button type="button" @click="tab = 'seo'" :class="{ 'dj-active': tab === 'seo' }" class="dj-admin-tab">{{ __('product_options.tab_seo') }}</button>
            </div>
        </div>

        <div class="dj-admin-card p-3 mb-4 flex items-center justify-between gap-3" x-show="wizardMode" x-cloak>
            <span class="text-sm font-medium text-[var(--dj-maroon-dark)]">
                {{ __('product_options.step_label') }} <span x-text="stepIndex + 1"></span> / <span x-text="steps.length"></span> —
                <span x-text="{
                    basic: '{{ __('product_options.tab_basic_info') }}',
                    images: '{{ __('product_options.tab_images') }}',
                    options: '{{ __('product_options.tab_options') }}',
                    variants: '{{ __('product_options.tab_variants') }}',
                    seo: '{{ __('product_options.tab_seo') }}',
                    review: '{{ __('product_options.review_heading') }}',
                }[tab]"></span>
            </span>
            <div class="flex gap-2 shrink-0">
                <button type="button" class="dj-admin-btn dj-admin-btn-secondary dj-admin-btn-sm" x-show="stepIndex > 0" @click="back()">{{ __('general.back') }}</button>
                <button type="button" class="dj-admin-btn dj-admin-btn-primary dj-admin-btn-sm" x-show="stepIndex < steps.length - 1" @click="next()">{{ __('product_options.next_step') }}</button>
            </div>
        </div>

        <div x-show="tab === 'basic' || tab === 'images'" x-cloak>
            <div class="dj-admin-card p-4 sm:p-6">
                <form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data" class="space-y-4">
                    @method('PUT')
                    <div x-show="tab === 'basic'" x-cloak data-autosave-form data-autosave-url="{{ route('admin.products.autosave', $product) }}">
                        @include('admin.products._form')
                    </div>
                    <div x-show="tab === 'images'" x-cloak>
                        @include('admin.products._images_tab')
                    </div>
                    <button type="submit" class="dj-admin-btn dj-admin-btn-primary" data-shortcut-save>{{ __('products.save_product') }}</button>
                </form>
            </div>
        </div>

        <div x-show="tab === 'options'" x-cloak>
            @include('admin.products._options_tab')
        </div>

        <div x-show="tab === 'variants'" x-cloak>
            @include('admin.products._variants_tab')
        </div>

        <div x-show="tab === 'seo'" x-cloak>
            <div class="dj-admin-card p-4 sm:p-6" data-autosave-form data-autosave-url="{{ route('admin.products.autosave', $product) }}">
                @include('admin.products._seo_tab')
            </div>
        </div>

        <div x-show="tab === 'review'" x-cloak>
            @include('admin.products._review_tab')
        </div>
    </div>
@endsection
