@extends('layouts.storefront')

@section('title', __('Get in Touch') . ' — Dar El-Jamila')
@section('meta_description', __("Have a question or a custom order? We're here to help"))

@section('content')
    <section class="dj-page-hero dj-photo-wrap dj-tint-maroon dj-strong">
        <img src="{{ setting_image_url($heroImage) }}" alt="">
        <div class="dj-mesh"><span></span><span></span><span></span></div>
        <div class="dj-particles" data-particles="12"></div>
        <div class="dj-lattice-bg"></div>
        <div class="dj-eyebrow">{{ __('Contact') }}</div>
        <h1>{{ __('Get in Touch') }}</h1>
        <p>{{ __("Have a question or a custom order? We're here to help") }}</p>
    </section>

    <div class="dj-contact-wrap">
        <form method="POST" action="{{ route('contact.store') }}" class="dj-contact-form">
            @csrf
            <div>
                <input type="text" name="name" value="{{ old('name') }}" placeholder="{{ __('Full Name') }}" aria-label="{{ __('Full Name') }}" required>
                @error('name') <p style="color:var(--dj-rose-dust); font-size:12px; margin-top:4px;">{{ $message }}</p> @enderror
            </div>
            <div>
                <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="{{ __('Phone Number') }}" aria-label="{{ __('Phone Number') }}">
            </div>
            <div>
                <input type="email" name="email" value="{{ old('email') }}" placeholder="{{ __('Email Address') }}" aria-label="{{ __('Email Address') }}" required>
                @error('email') <p style="color:var(--dj-rose-dust); font-size:12px; margin-top:4px;">{{ $message }}</p> @enderror
            </div>
            <div>
                <textarea name="message" rows="5" placeholder="{{ __('Write your message or order details here...') }}" aria-label="{{ __('Write your message or order details here...') }}" required>{{ old('message') }}</textarea>
                @error('message') <p style="color:var(--dj-rose-dust); font-size:12px; margin-top:4px;">{{ $message }}</p> @enderror
            </div>
            <button type="submit">{{ __('Send Message') }}</button>
        </form>

        <div class="dj-contact-info">
            <div class="dj-info-row">
                <div class="dj-ic">✉</div>
                <div><h4>{{ __('Email') }}</h4><p>{{ \App\Models\Setting::get('support_email', 'hello@dar-el-jamila.com') }}</p></div>
            </div>
            <div class="dj-info-row">
                <div class="dj-ic">📍</div>
                <div><h4>{{ __('Delivery') }}</h4><p>{{ __('Available nationwide') }}</p></div>
            </div>
            <div class="dj-info-row">
                <div class="dj-ic">🕐</div>
                <div><h4>{{ __('Response Hours') }}</h4><p>{{ __('Daily from 10 AM to 10 PM') }}</p></div>
            </div>
            @if ($whatsapp = \App\Models\Setting::get('whatsapp_number'))
                <a class="dj-whatsapp-cta" href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $whatsapp) }}" target="_blank" rel="noopener">{{ __('Message Us on WhatsApp 💬') }}</a>
            @endif
        </div>
    </div>
@endsection
