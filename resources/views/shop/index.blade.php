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

    {{-- Inline (not resources/css/app.css or resources/js/app.js) so this
         renders and behaves correctly the moment this Blade file reaches
         production via a plain git pull — this deploy process has twice now
         been observed running for a while on a compiled asset bundle that
         predates the current commit, with no npm run build in between. --}}
    <style>
        .dj-filters-toggle {
            display: none; margin: 0 auto 4px; padding: 10px 20px; border-radius: 30px; background: var(--dj-cream-2);
            color: var(--dj-maroon); font-size: 13.5px; font-weight: 600; align-items: center; gap: 8px;
        }
        .dj-filters-toggle svg { width: 16px; height: 16px; transition: transform .2s; }
        .dj-filters-toggle[aria-expanded="true"] svg { transform: rotate(180deg); }

        .dj-filters-panel {
            display: flex; flex-wrap: wrap; align-items: flex-end; gap: 14px; max-width: 1100px;
            margin: 0 auto 10px; padding: 18px 6%;
        }
        .dj-filters-field { display: flex; flex-direction: column; gap: 6px; flex: 1 1 130px; min-width: 110px; }
        /* Direct-child combinator only — the "Size" caption label is a direct
           child of .dj-filters-field, but each size-chip <label> is nested one
           level deeper inside .dj-filters-sizes. A plain descendant selector
           here would win on specificity over .dj-filters-size-chip's own
           color/font-size and repaint every chip in the wrong tone. */
        .dj-filters-field > label { font-size: 11.5px; color: var(--dj-rose-dust); text-transform: uppercase; letter-spacing: .5px; }
        .dj-filters-field input, .dj-filters-field select {
            padding: 12px 14px; border: 1.5px solid var(--dj-cream-2); border-radius: 12px; font-family: inherit;
            font-size: 14px; background: var(--dj-cream); color: var(--dj-ink); width: 100%;
        }
        .dj-filters-field input:focus, .dj-filters-field select:focus { outline: none; border-color: var(--dj-maroon); }

        /* Pill/chip treatment matching .dj-chip exactly (same border-radius,
           same --dj-cream-2/--dj-maroon default and --dj-maroon/--dj-gold
           selected-state colors) — native radio circle hidden, the <label>
           itself is the clickable pill. */
        .dj-filters-sizes { display: flex; flex-wrap: wrap; gap: 8px; }
        .dj-filters-size-chip {
            position: relative; padding: 9px 18px; border-radius: 30px; font-size: 13px; font-weight: 500;
            background: var(--dj-cream-2); color: var(--dj-maroon); cursor: pointer; transition: .2s;
        }
        .dj-filters-size-chip input { position: absolute; opacity: 0; width: 1px; height: 1px; pointer-events: none; }
        .dj-filters-size-chip:has(input:checked) { background: var(--dj-maroon); color: var(--dj-gold); }

        .dj-filters-actions { display: flex; align-items: center; gap: 16px; flex: 1 1 auto; }
        .dj-filters-apply { background: var(--dj-maroon); color: var(--dj-gold); font-weight: 700; padding: 12px 26px; border-radius: 12px; font-size: 14px; }
        .dj-filters-apply:hover { background: var(--dj-maroon-dark); }
        .dj-filters-clear { color: var(--dj-rose-dust); font-size: 13px; text-decoration: underline; }

        @media (max-width: 767px) {
            .dj-filters-toggle { display: flex; }
            .dj-filters-panel {
                max-height: 0; overflow: hidden; padding: 0 6%; margin-bottom: 0;
                transition: max-height .3s ease, padding .3s ease;
            }
            .dj-filters-panel.dj-open { padding: 16px 6%; }
            .dj-filters-field, .dj-filters-actions { flex-basis: 100% !important; }
        }
    </style>
    <script>
        function djToggleShopFilters(toggleEl) {
            const panel = document.getElementById('dj-shop-filters');
            if (!panel) return;
            const wasOpen = panel.classList.contains('dj-open');

            if (wasOpen) {
                panel.classList.remove('dj-open');
                panel.style.maxHeight = null;
                toggleEl.setAttribute('aria-expanded', 'false');
            } else {
                panel.classList.add('dj-open');
                panel.style.maxHeight = panel.scrollHeight + 'px';
                toggleEl.setAttribute('aria-expanded', 'true');
            }
        }
    </script>

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
