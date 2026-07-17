@extends('layouts.storefront')

@section('title', __('Dar El Jamila — Fashion Design House'))
@section('meta_description', __('Abayas and dresses crafted with care to highlight your elegance in every occasion — design that blends heritage with modernity.'))

@php
    $djShowcasePhotos = [
        'https://images.unsplash.com/photo-1772474500365-c2c520545f44?w=900&q=80&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1772474587292-08b3e8932acd?w=900&q=80&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1772474528936-4f1187eb1611?w=900&q=80&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1772474557170-4818d01d7bca?w=900&q=80&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1772474569781-2fb1c6539f8c?w=900&q=80&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1728487235101-664d87965931?w=900&q=80&auto=format&fit=crop',
    ];
@endphp

@section('content')

    <section class="dj-hero dj-photo-wrap dj-tint-maroon dj-strong">
        <img src="{{ setting_image_url($heroImage) }}" alt="">
        <div class="dj-mesh"><span></span><span></span><span></span></div>
        <div class="dj-particles" data-particles="18"></div>
        <div class="dj-lattice-bg"></div>
        <div class="dj-hero-eyebrow">{{ __('Dar El Jamila — Fashion Design House') }}</div>
        <h1>{{ __('Your Beauty Deserves an ') }}<span>{{ __('Exceptional Touch') }}</span></h1>
        <p>{{ __('Abayas and dresses crafted with care to highlight your elegance in every occasion — design that blends heritage with modernity.') }}</p>
        <a href="{{ route('shop.index') }}" class="dj-hero-cta">{{ __('Shop the Collection →') }}</a>
        <svg class="dj-thread" viewBox="0 0 1200 70" preserveAspectRatio="none"><path d="M0,35 C150,5 300,65 450,35 C600,5 750,65 900,35 C1000,15 1100,50 1200,35" /></svg>
    </section>

    @php
        $djSitewideOfferEndsAt = \App\Models\Setting::get('sitewide_offer_end_at');
        $djSitewideOfferEndsAt = $djSitewideOfferEndsAt ? \Illuminate\Support\Carbon::parse($djSitewideOfferEndsAt) : null;
    @endphp
    @include('partials.offer-countdown', ['endsAt' => $djSitewideOfferEndsAt, 'label' => \App\Models\Setting::get('sitewide_offer_label')])

    <section class="dj-stats">
        <div class="dj-stat-item"><span class="dj-stat-num" data-count="15700" data-suffix="+">0</span><p>{{ __('Instagram Followers') }}</p></div>
        <div class="dj-stat-item"><span class="dj-stat-num" data-count="88" data-suffix="">0</span><p>{{ __('Exclusive Designs') }}</p></div>
        <div class="dj-stat-item"><span class="dj-stat-num" data-count="100" data-suffix="%">0</span><p>{{ __('Customer Satisfaction') }}</p></div>
        <div class="dj-stat-item"><span class="dj-stat-num" data-count="27" data-suffix="">0</span><p>{{ __('Governorates Served') }}</p></div>
    </section>

    <div class="dj-categories">
        <a href="{{ route('shop.index') }}" class="dj-chip {{ ! request('category') ? 'dj-active' : '' }}">{{ __('All') }}</a>
        @foreach ($categories as $category)
            <a href="{{ route('shop.index', ['category' => $category->slug]) }}" class="dj-chip">{{ trans_field($category, 'name') }}</a>
        @endforeach
    </div>

    <div class="dj-ornament"><div class="dj-ln"></div><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.4 6.8L21 11l-6.6 2.2L12 20l-2.4-6.8L3 11l6.6-2.2L12 2z"/></svg><div class="dj-ln dj-r"></div></div>
    <div class="dj-section-title"><h2>{{ __('Featured Collection') }}</h2><p>{{ __('Every piece designed to highlight your natural beauty') }}</p></div>
    <div class="dj-grid">
        @forelse ($featuredProducts as $product)
            @include('shop.partials.product-card', ['product' => $product])
        @empty
            <p class="text-center col-span-full" style="color:#8a6b70; grid-column: 1 / -1;">{{ __('No featured products yet.') }}</p>
        @endforelse
    </div>

    @if ($trendingProducts->isNotEmpty())
        <div class="dj-section-title"><h2>{{ __('Trending Now') }}</h2><p>{{ __('What customers are buying most this month') }}</p></div>
        <div class="dj-grid">
            @foreach ($trendingProducts as $product)
                @include('shop.partials.product-card', ['product' => $product])
            @endforeach
        </div>
    @endif

    <div class="dj-section-title"><h2>{{ __('Shop by Category') }}</h2><p>{{ __("Find exactly what you're looking for, fast") }}</p></div>
    <div class="dj-cat-showcase">
        @foreach ($categories as $category)
            <a href="{{ route('shop.index', ['category' => $category->slug]) }}" class="dj-cat-tile dj-reveal">
                <div class="dj-photo-wrap dj-tint-maroon" style="position:absolute; inset:0;">
                    <img src="{{ $category->image_thumb ?? $djShowcasePhotos[$loop->index % count($djShowcasePhotos)] }}" alt="{{ trans_field($category, 'name') }}">
                </div>
                <span class="dj-arrow">{{ app()->getLocale() === 'ar' ? '←' : '→' }}</span>
                <div class="dj-cap"><h3>{{ trans_field($category, 'name') }}</h3></div>
            </a>
        @endforeach
    </div>

    @if ($collections->isNotEmpty())
        <div class="dj-section-title"><h2>{{ __('Shop by Collection') }}</h2><p>{{ __('Curated edits for every mood and moment') }}</p></div>
        <div class="dj-cat-showcase">
            @foreach ($collections as $collection)
                <a href="{{ route('shop.index', ['collection' => $collection->slug]) }}" class="dj-cat-tile dj-reveal">
                    <div class="dj-photo-wrap dj-tint-maroon" style="position:absolute; inset:0;">
                        <img src="{{ $collection->image_url }}" alt="{{ trans_field($collection, 'name') }}">
                    </div>
                    <span class="dj-arrow">{{ app()->getLocale() === 'ar' ? '←' : '→' }}</span>
                    <div class="dj-cap"><h3>{{ trans_field($collection, 'name') }}</h3></div>
                </a>
            @endforeach
        </div>
    @endif

    @if ($offerBanners->isNotEmpty())
        <div class="dj-section-title"><h2>{{ __('Special Offers') }}</h2><p>{{ __('Limited-time savings across the collection') }}</p></div>
        <div class="dj-editorial">
            @foreach ($offerBanners as $banner)
                <a href="{{ $banner->link_url ?? route('shop.index') }}" class="dj-ed-tile dj-photo-wrap dj-tint-maroon dj-reveal">
                    <img src="{{ $banner->image_url }}" alt="{{ trans_field($banner, 'title') }}">
                    <div class="dj-ed-caption">
                        <h4>{{ trans_field($banner, 'title') }}</h4>
                        @if (trans_field($banner, 'subtitle'))
                            <p>{{ trans_field($banner, 'subtitle') }}</p>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    @endif

    <section class="dj-trust-strip">
        <div class="dj-trust-item dj-reveal"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2 4 6v6c0 5 3.5 8.5 8 10 4.5-1.5 8-5 8-10V6l-8-4z"/></svg><span>{{ __('Safe & Trusted Ordering') }}</span></div>
        <div class="dj-trust-item dj-reveal"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a4 4 0 018 0v2"/></svg><span>{{ __('Nationwide Delivery') }}</span></div>
        <div class="dj-trust-item dj-reveal"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 4h16v4H4zM4 8l2 12h12l2-12"/></svg><span>{{ __('Easy 3-Day Exchange') }}</span></div>
        <div class="dj-trust-item dj-reveal"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2l2.4 6.8L21 11l-6.6 2.2L12 20l-2.4-6.8L3 11l6.6-2.2L12 2z"/></svg><span>{{ __('100% Quality Guaranteed') }}</span></div>
    </section>

    <div class="dj-section-title"><h2>{{ __('Behind the Scenes') }}</h2><p>{{ __('Glimpses from the Dar El Jamila world') }}</p></div>
    <div class="dj-editorial">
        @php
            $djEditorial = [
                [$djShowcasePhotos[1], __('Burgundy Nights'), __('Occasion Collection')],
                [$djShowcasePhotos[4], __('Golden Touch'), __('Exclusive Details')],
                [$djShowcasePhotos[3], __('Handcrafted'), __('Precise Embroidery')],
            ];
        @endphp
        @foreach ($djEditorial as [$img, $title, $caption])
            <div class="dj-ed-tile dj-photo-wrap dj-tint-maroon dj-reveal">
                <img src="{{ $img }}" alt="">
                <div class="dj-ed-caption"><h4>{{ $title }}</h4><p>{{ $caption }}</p></div>
            </div>
        @endforeach
    </div>

    <section class="dj-testimonials">
        <div class="dj-ornament"><div class="dj-ln"></div><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.4 6.8L21 11l-6.6 2.2L12 20l-2.4-6.8L3 11l6.6-2.2L12 2z"/></svg><div class="dj-ln dj-r"></div></div>
        <div class="dj-section-title"><h2>{{ __('What Our Customers Say') }}</h2><p>{{ __("Our customers' trust is our pride") }}</p></div>
        <div class="dj-test-grid">
            <div class="dj-test-card dj-reveal">
                <div class="dj-stars">★★★★★</div>
                <p>"{{ __('The fabric is incredibly luxurious and the tailoring is millimeter-precise. I genuinely felt like it was made just for me.') }}"</p>
                <div class="dj-test-author"><div class="dj-avatar">س</div><span>Sara A.</span></div>
            </div>
            <div class="dj-test-card dj-reveal">
                <div class="dj-stars">★★★★★</div>
                <p>"{{ __('Delivery was fast and the packaging was so elegant. The abaya looked even better than in the photo.') }}"</p>
                <div class="dj-test-author"><div class="dj-avatar">م</div><span>Mona K.</span></div>
            </div>
            <div class="dj-test-card dj-reveal">
                <div class="dj-stars">★★★★★</div>
                <p>"{{ __('Dar El Jamila has become my go-to for every occasion. The designs are one-of-a-kind.') }}"</p>
                <div class="dj-test-author"><div class="dj-avatar">ه</div><span>Heba S.</span></div>
            </div>
        </div>
    </section>

    <section class="dj-usp">
        <div class="dj-lattice-bg" style="opacity:.08;"></div>
        <div class="dj-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2l2.4 6.8L21 11l-6.6 2.2L12 20l-2.4-6.8L3 11l6.6-2.2L12 2z"/></svg><h3>{{ __('Carefully Selected Fabrics') }}</h3><p>{{ __('Premium fabrics that last, giving you a refined look down to every detail.') }}</p></div>
        <div class="dj-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a4 4 0 018 0v2"/></svg><h3>{{ __('Nationwide Delivery') }}</h3><p>{{ __('We deliver wherever you are, in elegant packaging worthy of your occasion.') }}</p></div>
        <div class="dj-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 6L9 17l-5-5"/></svg><h3>{{ __('Exclusive Design') }}</h3><p>{{ __("Limited in-house designs you won't find anywhere else.") }}</p></div>
    </section>

    <section class="dj-insta-strip">
        <div class="dj-section-title"><h2>{{ __('Follow Us on Instagram') }}</h2><p>@dar_el_jamila</p></div>
        <div class="dj-insta-grid">
            @foreach ($djShowcasePhotos as $photo)
                <div class="dj-insta-tile dj-photo-wrap"><img src="{{ $photo }}" alt=""></div>
            @endforeach
        </div>
    </section>

    <section class="dj-cta-band">
        <div class="dj-mesh"><span></span><span></span><span></span></div>
        <h2>{{ __('Want a Piece Tailored Just for You?') }}</h2>
        <p>{{ __('Discover our custom tailoring service and everything else we offer') }}</p>
        <a href="{{ route('services') }}" class="dj-hero-cta" style="position:relative;">{{ __('Explore Our Services →') }}</a>
    </section>

    <section class="dj-newsletter">
        <div class="dj-mesh"><span></span><span></span><span></span></div>
        <h2>{{ __('Be the First to Know') }}</h2>
        <p>{{ __('Subscribe to get our latest collections and exclusive offers') }}</p>
        <form method="POST" action="{{ route('newsletter.store') }}" class="dj-newsletter-form">
            @csrf
            <input type="email" name="email" required placeholder="{{ __('Your email address') }}" aria-label="{{ __('Your email address') }}">
            <button type="submit">{{ __('Subscribe') }}</button>
        </form>
    </section>

    @if ($latestPosts->isNotEmpty())
        <div class="dj-section-title"><h2>{{ __('From the Journal') }}</h2><p>{{ __('Style tips, occasion looks, and behind-the-scenes from the Dar El Jamila world') }}</p></div>
        <div class="dj-blog-grid">
            @foreach ($latestPosts as $post)
                <a href="{{ route('blog.show', $post) }}" class="dj-blog-card dj-reveal">
                    <div class="dj-blog-cover dj-photo-wrap dj-tint-maroon">
                        @if ($post->cover_image)
                            <img src="{{ \Illuminate\Support\Str::startsWith($post->cover_image, ['http://','https://']) ? $post->cover_image : asset('storage/'.$post->cover_image) }}" alt="">
                        @endif
                    </div>
                    <div class="dj-blog-body">
                        <div class="dj-blog-date">{{ $post->published_at?->translatedFormat('F j, Y') }}</div>
                        <h3>{{ trans_field($post, 'title') }}</h3>
                        <p>{{ \Illuminate\Support\Str::limit(trans_field($post, 'excerpt'), 100) }}</p>
                        <span class="dj-read-more">{{ __('Read More →') }}</span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif

    <div class="dj-section-title"><h2>{{ __('Frequently Asked Questions') }}</h2><p>{{ __('Quick answers to the questions we get most') }}</p></div>
    @include('partials.faq-accordion', ['faqs' => [
        ['q' => __('How long does delivery take?'), 'a' => __('Delivery takes 2 to 5 business days depending on the governorate, and we send you a tracking number once your order ships.')],
        ['q' => __('How do I choose the right size?'), 'a' => __('We have a detailed size chart available, and our team is happy to help you pick the right size before confirming your order.')],
        ['q' => __('Can I exchange or return an item?'), 'a' => __('Yes, exchanges are available within 3 days of delivery, as long as the item is unused and in its original condition.')],
        ['q' => __('Can I request a custom design or size?'), 'a' => __("Absolutely — our custom tailoring service is available. Reach out and we'll help you design your piece exactly the way you want.")],
        ['q' => __('What payment methods are available?'), 'a' => __('We currently accept cash on delivery, with more payment options coming soon.')],
    ]])

@endsection
