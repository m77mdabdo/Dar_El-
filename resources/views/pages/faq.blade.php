@extends('layouts.storefront')

@section('title', __('Frequently Asked Questions') . ' — Dar El Jamila')
@section('meta_description', __('Quick answers to the questions we get most about delivery, sizing, exchanges, and payment.'))

@section('content')
    <section class="dj-page-hero dj-photo-wrap dj-tint-maroon dj-strong">
        <img src="{{ setting_image_url($heroImage) }}" alt="">
        <div class="dj-mesh"><span></span><span></span><span></span></div>
        <div class="dj-particles" data-particles="12"></div>
        <div class="dj-lattice-bg"></div>
        <div class="dj-eyebrow">{{ __('Frequently Asked Questions') }}</div>
        <h1>{{ __('Quick Answers, Whenever You Need Them') }}</h1>
        <p>{{ __('Quick answers to the questions we get most') }}</p>
    </section>

    <div class="dj-faq-wrap" style="padding-top:50px;">
        @include('partials.faq-accordion', ['faqs' => $faqs])
    </div>

    @if ($whatsapp = \App\Models\Setting::get('whatsapp_number'))
        <section class="dj-cta-band">
            <div class="dj-mesh"><span></span><span></span><span></span></div>
            <h2>{{ __("Still Have a Question?") }}</h2>
            <p>{{ __("Message us directly on WhatsApp and we'll help right away.") }}</p>
            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $whatsapp) }}?text={{ rawurlencode('مرحبًا، لدي سؤال.') }}"
               target="_blank" rel="noopener" class="dj-hero-cta" style="position:relative;">{{ __('Message Us on WhatsApp 💬') }}</a>
        </section>
    @endif
@endsection
