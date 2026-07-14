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

    <div class="dj-categories">
        <a href="{{ route('shop.index') }}" class="dj-chip {{ ! request('category') ? 'dj-active' : '' }}">{{ __('All') }}</a>
        @foreach ($categories as $category)
            <a href="{{ route('shop.index', array_filter(['category' => $category->slug, 'sort' => request('sort')])) }}"
               class="dj-chip {{ request('category') === $category->slug ? 'dj-active' : '' }}">
                {{ trans_field($category, 'name') }}
            </a>
        @endforeach
    </div>

    <div class="max-w-xs mx-auto px-4" style="max-width:220px; margin:0 auto 10px;">
        <form method="GET">
            @if (request('category'))
                <input type="hidden" name="category" value="{{ request('category') }}">
            @endif
            <select name="sort" onchange="this.form.submit()" aria-label="{{ __('Sort by') }}" class="w-full text-sm rounded-full border-cream-2" style="border:1.5px solid var(--dj-cream-2); padding:8px 14px; background:#fff; color:var(--dj-maroon);">
                <option value="">{{ __('Newest') }}</option>
                <option value="price_asc" @selected(request('sort') === 'price_asc')>{{ __('Price: Low to High') }}</option>
                <option value="price_desc" @selected(request('sort') === 'price_desc')>{{ __('Price: High to Low') }}</option>
            </select>
        </form>
    </div>

    <div class="dj-grid">
        @forelse ($products as $product)
            @include('shop.partials.product-card', ['product' => $product])
        @empty
            <div style="grid-column:1/-1; text-align:center; padding:60px 20px; color:#8a6b70;">
                <p style="font-size:15px;">{{ __('No products found.') }}</p>
                <a href="{{ route('shop.index') }}" class="dj-hero-cta" style="position:relative; margin-top:20px; display:inline-flex;">{{ __('View All Products') }}</a>
            </div>
        @endforelse
    </div>

    <div class="max-w-7xl mx-auto px-4 pb-16">
        {{ $products->links() }}
    </div>
@endsection
