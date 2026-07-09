@extends('admin.layout')

@section('title', __('products.add_product'))

@section('content')
    <div class="max-w-4xl" x-data="{ tab: 'basic' }">
        <div class="dj-admin-tabs">
            <button type="button" @click="tab = 'basic'" :class="{ 'dj-active': tab === 'basic' }" class="dj-admin-tab">{{ __('product_options.tab_basic_info') }}</button>
            <button type="button" class="dj-admin-tab dj-disabled" disabled title="{{ __('product_options.save_basic_info_first') }}">{{ __('product_options.tab_options') }}</button>
            <button type="button" class="dj-admin-tab dj-disabled" disabled title="{{ __('product_options.save_basic_info_first') }}">{{ __('product_options.tab_variants') }}</button>
            <button type="button" class="dj-admin-tab dj-disabled" disabled title="{{ __('product_options.save_basic_info_first') }}">{{ __('product_options.tab_images') }}</button>
            <button type="button" class="dj-admin-tab dj-disabled" disabled title="{{ __('product_options.save_basic_info_first') }}">{{ __('product_options.tab_seo') }}</button>
        </div>

        <div x-show="tab === 'basic'" x-cloak>
            <p class="dj-admin-hint mb-4">{{ __('product_options.save_basic_info_first') }}</p>
            <div class="dj-admin-card p-4 sm:p-6">
                <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data" class="space-y-4">
                    @include('admin.products._form')
                    <button type="submit" class="dj-admin-btn dj-admin-btn-primary">{{ __('products.save_product') }}</button>
                </form>
            </div>
        </div>
    </div>
@endsection
