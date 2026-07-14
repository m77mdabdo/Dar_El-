@extends('layouts.storefront')

@section('title', __('About Us') . ' — Dar El Jamila')
@section('meta_description', __('A story of passion that started with a simple dream: fashion that reflects the identity of the Arab woman with a modern touch'))

@section('content')
    <section class="dj-page-hero dj-photo-wrap dj-tint-maroon dj-strong">
        <img src="{{ $heroImage }}" alt="">
        <div class="dj-mesh"><span></span><span></span><span></span></div>
        <div class="dj-particles" data-particles="12"></div>
        <div class="dj-lattice-bg"></div>
        <div class="dj-eyebrow">{{ __('Our Story') }}</div>
        <h1>{{ __('Dar El Jamila') }}</h1>
        <p>{{ __('A story of passion that started with a simple dream: fashion that reflects the identity of the Arab woman with a modern touch') }}</p>
    </section>

    <div class="dj-about-story">
        <div class="dj-story-visual dj-photo-wrap dj-tint-maroon dj-reveal">
            <img src="{{ $storyImage }}" alt="">
            <div class="dj-lattice-bg" style="opacity:.15;"></div>
        </div>
        <div class="dj-story-text dj-reveal">
            <div class="dj-eyebrow">{{ __('About Us') }}</div>
            <h2>{{ __('Craftsmanship That Tells a Story') }}</h2>
            <p>{{ __("Dar El Jamila began as a small idea inside a design studio, born from the passion of a woman who believed an abaya is more than fabric — it's identity, presence, and confidence.") }}</p>
            <p>{{ __('Today, after years of continuous growth, Dar El Jamila is trusted by over 15,000 followers, thanks to close attention to detail and the finest fabric choices.') }}</p>
            <p>{{ __("Every piece is designed and tailored with care, so what reaches you isn't just clothing — it's a complete experience of luxury and comfort.") }}</p>
        </div>
    </div>

    <section class="dj-timeline">
        <div class="dj-section-title"><h2>{{ __('Our Journey') }}</h2><p>{{ __('Milestones along the Dar El Jamila journey') }}</p></div>
        <div class="dj-timeline-track" style="margin-top:40px;">
            <div class="dj-t-item dj-reveal">
                <span class="dj-yr">{{ __('The Beginning') }}</span>
                <h4>{{ __('First Design') }}</h4>
                <p>{{ __('It all started with a passion for design and tailoring custom abayas for private orders only.') }}</p>
            </div>
            <div class="dj-t-item dj-reveal">
                <span class="dj-yr">{{ __('Growth') }}</span>
                <h4>{{ __('Official Page Launch') }}</h4>
                <p>{{ __('The Dar El Jamila Instagram account launched, expanding our customer base across many governorates.') }}</p>
            </div>
            <div class="dj-t-item dj-reveal">
                <span class="dj-yr">{{ __('Expansion') }}</span>
                <h4>{{ __('Seasonal Collections') }}</h4>
                <p>{{ __('Launching collections tailored to seasons and occasions, with exclusive limited pieces.') }}</p>
            </div>
            <div class="dj-t-item dj-reveal">
                <span class="dj-yr">{{ __('Today') }}</span>
                <h4>{{ __('+15,700 Followers') }}</h4>
                <p>{{ __('Growing trust from customers across every region, with delivery covering all areas.') }}</p>
            </div>
        </div>
    </section>

    <section class="dj-values">
        <div class="dj-section-title"><h2>{{ __('Our Values') }}</h2><p>{{ __('The principles we live by') }}</p></div>
        <div class="dj-values-grid">
            <div class="dj-value-card dj-reveal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2l2.4 6.8L21 11l-6.6 2.2L12 20l-2.4-6.8L3 11l6.6-2.2L12 2z"/></svg>
                <h3>{{ __('Quality First') }}</h3>
                <p>{{ __('We never compromise on fabric quality or tailoring precision, no matter what.') }}</p>
            </div>
            <div class="dj-value-card dj-reveal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 6L9 17l-5-5"/></svg>
                <h3>{{ __('Authenticity') }}</h3>
                <p>{{ __('Designs inspired by Arab identity with a contemporary spirit.') }}</p>
            </div>
            <div class="dj-value-card dj-reveal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>
                <h3>{{ __('Customer Trust') }}</h3>
                <p>{{ __('Every shopping experience with us is designed for your comfort and full confidence.') }}</p>
            </div>
        </div>
    </section>
@endsection
