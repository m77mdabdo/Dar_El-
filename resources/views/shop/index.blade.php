@extends('layouts.storefront')

@section('title', __('Shop') . ' — Dar El Jamila')
@section('meta_description', __("Abayas, evening dresses, isdal and accessories, all hand-picked with care"))

@section('content')
    <section class="dj-page-hero dj-photo-wrap dj-tint-maroon dj-strong">
        <img src="{{ $heroImage }}" alt="">
        <div class="dj-mesh"><span></span><span></span><span></span></div>
        <div class="dj-particles" data-particles="12"></div>
        <div class="dj-lattice-bg"></div>
        <div class="dj-eyebrow">{{ __('Shop') }}</div>
        <h1>{{ __('Shop the Full Collection') }}</h1>
        <p>{{ __('Abayas, evening dresses, isdal and accessories, all hand-picked with care') }}</p>
    </section>

    @php
        // Every current query param except `category`/`page` — merged into
        // each category chip's link so switching category never silently
        // drops a search/price/size/sort the customer already set.
        $chipParams = request()->except(['category', 'page']);
    @endphp

    <div class="dj-categories">
        <a href="{{ route('shop.index', array_filter($chipParams)) }}" class="dj-chip {{ ! request('category') ? 'dj-active' : '' }}">{{ __('All') }}</a>
        @foreach ($categories as $category)
            <a href="{{ route('shop.index', array_filter($chipParams + ['category' => $category->slug])) }}"
               class="dj-chip {{ request('category') === $category->slug ? 'dj-active' : '' }}">
                {{ trans_field($category, 'name') }}
            </a>
        @endforeach
    </div>

    <button type="button" class="dj-filters-toggle" onclick="djToggleShopFilters(this)" aria-expanded="false">
        <span>{{ __('Filters') }}</span>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
    </button>

    <form method="GET" class="dj-filters-panel" id="dj-shop-filters">
        @if (request('category'))
            <input type="hidden" name="category" value="{{ request('category') }}">
        @endif
        @if (request('collection'))
            <input type="hidden" name="collection" value="{{ request('collection') }}">
        @endif

        <div class="dj-filters-field" style="flex-basis:220px;">
            <label for="dj-filter-q">{{ __('Search') }}</label>
            <input type="search" id="dj-filter-q" name="q" value="{{ request('q') }}" placeholder="{{ __('Search products...') }}">
        </div>

        <div class="dj-filters-field">
            <label for="dj-filter-min">{{ __('Min price') }}</label>
            <input type="number" min="0" id="dj-filter-min" name="min_price" value="{{ request('min_price') }}">
        </div>

        <div class="dj-filters-field">
            <label for="dj-filter-max">{{ __('Max price') }}</label>
            <input type="number" min="0" id="dj-filter-max" name="max_price" value="{{ request('max_price') }}">
        </div>

        @if ($availableSizes->isNotEmpty())
            <div class="dj-filters-field" style="flex-basis:100%;">
                <label>{{ __('Size') }}</label>
                <div class="dj-filters-sizes">
                    <label class="dj-filters-size-chip">
                        <input type="radio" name="size" value="" onchange="this.form.submit()" @checked(! request('size'))>
                        <span>{{ __('All Sizes') }}</span>
                    </label>
                    @foreach ($availableSizes as $sizeOption)
                        <label class="dj-filters-size-chip">
                            <input type="radio" name="size" value="{{ $sizeOption }}" onchange="this.form.submit()" @checked(request('size') === $sizeOption)>
                            <span>{{ $sizeOption }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="dj-filters-field">
            <label for="dj-filter-sort">{{ __('Sort by') }}</label>
            <select id="dj-filter-sort" name="sort" onchange="this.form.submit()">
                <option value="">{{ __('Newest') }}</option>
                <option value="price_asc" @selected(request('sort') === 'price_asc')>{{ __('Price: Low to High') }}</option>
                <option value="price_desc" @selected(request('sort') === 'price_desc')>{{ __('Price: High to Low') }}</option>
            </select>
        </div>

        <div class="dj-filters-actions">
            <button type="submit" class="dj-filters-apply">{{ __('Apply Filters') }}</button>
            @if (request()->hasAny(['q', 'min_price', 'max_price', 'size']))
                <a href="{{ route('shop.index', array_filter(request()->only(['category', 'collection']))) }}" class="dj-filters-clear">{{ __('Clear Filters') }}</a>
            @endif
        </div>
    </form>

    <div class="dj-grid">
        @forelse ($products as $product)
            @include('shop.partials.product-card', ['product' => $product])
        @empty
            <div style="grid-column:1/-1; text-align:center; padding:60px 20px; color:#8a6b70;">
                <p style="font-size:15px;">{{ __("We couldn't find any pieces matching your search — try adjusting the filters.") }}</p>
                <a href="{{ route('shop.index') }}" class="dj-hero-cta" style="position:relative; margin-top:20px; display:inline-flex;">{{ __('View All Products') }}</a>
            </div>
        @endforelse
    </div>

    <div class="max-w-7xl mx-auto px-4 pb-16">
        {{ $products->links() }}
    </div>
@endsection
