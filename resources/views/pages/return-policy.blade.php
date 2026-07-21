@extends('layouts.storefront')

@section('title', __('Return & Exchange Policy') . ' — Dar El Jamila')
@section('meta_description', __('Everything you need to know about exchanging or returning an order from Dar El Jamila.'))

@section('content')
    <section class="dj-page-hero dj-photo-wrap dj-tint-maroon dj-strong">
        <img src="{{ setting_image_url($heroImage) }}" alt="">
        <div class="dj-mesh"><span></span><span></span><span></span></div>
        <div class="dj-particles" data-particles="12"></div>
        <div class="dj-lattice-bg"></div>
        <div class="dj-eyebrow">{{ __('Return & Exchange Policy') }}</div>
        <h1>{{ __("We Want You to Love What You Ordered") }}</h1>
        <p>{{ __('A clear, simple exchange process — because feeling confident in your purchase matters to us.') }}</p>
    </section>

    <div class="dj-faq-wrap" style="padding-top:50px;">
        <div class="dj-section-title" style="margin-bottom:36px;">
            <h2>{{ __('Exchange & Return Questions') }}</h2>
            <p>{{ __("If you don't find your answer here, message us directly on WhatsApp and we'll help right away.") }}</p>
        </div>

        @include('partials.faq-accordion', ['faqs' => [
            [
                'q' => __('How long do I have to request an exchange?'),
                'a' => __("You can request an exchange within 3 days of receiving your order. We count from the delivery date shown in your shipment tracking, so please reach out as soon as possible if you'd like to exchange an item."),
            ],
            [
                'q' => __('What condition must the item be in?'),
                'a' => __('The item must be unworn and in its original condition, with all tags still attached, and free of any perfume, makeup, or wash marks. Custom-tailored pieces are only eligible for exchange in the case of a manufacturing defect.'),
            ],
            [
                'q' => __('How do I start an exchange?'),
                'a' => __('Message us directly on WhatsApp with your order number, the reason for the exchange, and a photo of the item. Our team will review your request and respond within one business day with the next steps and available sizes or alternatives.'),
            ],
            [
                'q' => __('Can I get a refund instead of an exchange?'),
                'a' => __("Since payment is cash on delivery and we don't store any electronic payment details, our default policy is an exchange for another item of equal or adjusted value. If no suitable alternative is available, a refund can be arranged via bank transfer or e-wallet after coordinating with us directly."),
            ],
            [
                'q' => __('Are any items excluded from this policy?'),
                'a' => __('Special offers and final-sale items are not eligible for exchange or return unless there is a clear manufacturing defect. Pieces custom-tailored to your own measurements are only eligible for exchange in the case of a manufacturing defect.'),
            ],
            [
                'q' => __('Who covers shipping costs for an exchange?'),
                'a' => __('If the exchange is due to a defect or an error on our part, we cover shipping costs in full. If the exchange is by your own preference (for example, a different size), a small shipping fee applies, which we\'ll clarify when you reach out.'),
            ],
        ]])
    </div>

    @if ($whatsapp = \App\Models\Setting::get('whatsapp_number'))
        <section class="dj-cta-band">
            <div class="dj-mesh"><span></span><span></span><span></span></div>
            <h2>{{ __('Ready to Request an Exchange?') }}</h2>
            <p>{{ __('Message us your order number and we’ll take it from there.') }}</p>
            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $whatsapp) }}?text={{ rawurlencode('مرحبًا، أرغب في استبدال قطعة من طلبي.') }}"
               target="_blank" rel="noopener" class="dj-hero-cta" style="position:relative;">{{ __('Message Us on WhatsApp 💬') }}</a>
        </section>
    @endif
@endsection
