@extends('layouts.storefront')

@section('title', __('Services') . ' — Dar El Jamila')
@section('meta_description', __('A range of services designed to make your Dar El Jamila experience complete from start to finish'))

@section('content')
    <section class="dj-page-hero dj-photo-wrap dj-tint-maroon dj-strong">
        <img src="{{ setting_image_url($heroImage) }}" alt="">
        <div class="dj-mesh"><span></span><span></span><span></span></div>
        <div class="dj-particles" data-particles="12"></div>
        <div class="dj-lattice-bg"></div>
        <div class="dj-eyebrow">{{ __('Our Services') }}</div>
        <h1>{{ __('More Than Just Shopping') }}</h1>
        <p>{{ __('A range of services designed to make your Dar El Jamila experience complete from start to finish') }}</p>
    </section>

    <div class="dj-services-grid">
        @foreach ($services as $djService)
            <div class="dj-service-card dj-reveal">
                <div class="dj-service-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">{!! $djService['icon'] !!}</svg></div>
                <h3>{{ $djService['title'] }}</h3>
                <p>{{ $djService['description'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="dj-section-title"><h2>{{ __('How to Order') }}</h2><p>{{ __('4 simple steps and your order is on its way') }}</p></div>
    <div class="dj-steps-wrap">
        <div class="dj-step-item dj-reveal"><div class="dj-step-num">1</div><h4>{{ __('Choose Your Piece') }}</h4><p>{{ __('Browse the collection and pick the piece you love.') }}</p></div>
        <div class="dj-step-item dj-reveal"><div class="dj-step-num">2</div><h4>{{ __('Get in Touch') }}</h4><p>{{ __('Message us on WhatsApp or DM to confirm the details.') }}</p></div>
        <div class="dj-step-item dj-reveal"><div class="dj-step-num">3</div><h4>{{ __('Confirm Size & Color') }}</h4><p>{{ __("With our team's help, pick the right size and color.") }}</p></div>
        <div class="dj-step-item dj-reveal"><div class="dj-step-num">4</div><h4>{{ __('Receive Your Order') }}</h4><p>{{ __('Your order arrives at your door, beautifully packaged.') }}</p></div>
    </div>

    <section class="dj-cta-band" style="margin-top:70px;">
        <div class="dj-mesh"><span></span><span></span><span></span></div>
        <h2>{{ __('Ready to Get Started?') }}</h2>
        <p>{{ __("Reach out now and let's help you find your perfect look") }}</p>
        <a href="{{ route('contact.show') }}" class="dj-hero-cta" style="position:relative;">{{ __('Contact Us →') }}</a>
    </section>
@endsection
