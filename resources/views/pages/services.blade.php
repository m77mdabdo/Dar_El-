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
        <div class="dj-service-card dj-reveal">
            <div class="dj-service-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/></svg></div>
            <h3>{{ __('Custom Tailoring') }}</h3>
            <p>{{ __('We tailor your piece to your exact measurements, with your choice of color and details to match your taste.') }}</p>
        </div>
        <div class="dj-service-card dj-reveal">
            <div class="dj-service-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 6h18M3 12h18M3 18h18"/><circle cx="7" cy="6" r="1.4" fill="currentColor" stroke="none"/><circle cx="15" cy="12" r="1.4" fill="currentColor" stroke="none"/><circle cx="10" cy="18" r="1.4" fill="currentColor" stroke="none"/></svg></div>
            <h3>{{ __('Size Consultation') }}</h3>
            <p>{{ __('A detailed size chart and a team ready to help you pick the perfect fit before you confirm your order.') }}</p>
        </div>
        <div class="dj-service-card dj-reveal">
            <div class="dj-service-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a4 4 0 018 0v2"/></svg></div>
            <h3>{{ __('Nationwide Delivery') }}</h3>
            <p>{{ __('Delivery service covering every governorate, with order tracking from confirmation to your doorstep.') }}</p>
        </div>
        <div class="dj-service-card dj-reveal">
            <div class="dj-service-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 12v9H4v-9M2 7h20v5H2zM12 22V7M12 7C10 3 6 3 6 6s4 1 6 1M12 7c2-4 6-4 6-1s-4 1-6 1"/></svg></div>
            <h3>{{ __('Luxury Gift Wrapping') }}</h3>
            <p>{{ __('If your order is a gift, we wrap it beautifully to match the occasion — no extra request needed.') }}</p>
        </div>
        <div class="dj-service-card dj-reveal">
            <div class="dj-service-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 002 1.6h9.7a2 2 0 002-1.6L23 6H6"/></svg></div>
            <h3>{{ __('Events & Bulk Orders') }}</h3>
            <p>{{ __('We help coordinate matching looks for weddings, engagements, and graduation parties with special quantities and pricing.') }}</p>
        </div>
        <div class="dj-service-card dj-reveal">
            <div class="dj-service-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg></div>
            <h3>{{ __('Flexible Payment Options') }}</h3>
            <p>{{ __('Pay on delivery, with more payment options coming soon.') }}</p>
        </div>
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
