<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Verify Your Email') }} — Dar El Jamila</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Aref+Ruqaa:wght@400;700&family=Tajawal:wght@300;400;500;700;900&family=Playfair+Display:ital,wght@0,500;0,700;1,500&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="preload" as="image" href="https://images.unsplash.com/photo-1772474528936-4f1187eb1611?w=1600&q=85&auto=format&fit=crop">
    @include('partials.favicon-links')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="dj-login-page {{ app()->getLocale() === 'en' ? 'dj-en' : '' }}">

    <div class="dj-login-bg" style="background-image:url('https://images.unsplash.com/photo-1772474528936-4f1187eb1611?w=1600&q=85&auto=format&fit=crop');"></div>
    <div class="dj-login-overlay"></div>
    <div class="dj-login-lattice dj-lattice-bg"></div>

    <div class="dj-login-card">
        <div class="dj-login-lang">
            <div class="dj-lang-pill">
                <span class="dj-globe" aria-hidden="true">🌍</span>
                <a href="{{ route('lang.switch', 'ar') }}" class="{{ app()->getLocale() === 'ar' ? 'dj-active' : '' }}">عربي</a>
                <a href="{{ route('lang.switch', 'en') }}" class="{{ app()->getLocale() === 'en' ? 'dj-active' : '' }}">English</a>
            </div>
        </div>

        <div class="dj-login-brand">
            <x-brand-logo style="height:44px;width:auto;margin-inline:auto;" />
            <div class="dj-login-tagline">{{ __('Timeless Elegance. Crafted for You.') }}</div>
        </div>

        <div class="dj-login-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 6.5A2.5 2.5 0 0 1 6.5 4h11A2.5 2.5 0 0 1 20 6.5v11a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 17.5v-11Z"/><path d="m5 6.5 7 5.5 7-5.5"/><path d="m14 15 2.5 2.5L21 13"/></svg>
        </div>

        <div class="dj-login-heading">
            <h1>{{ __('Verify Your Email') }}</h1>
            <p>{{ __('Almost there! Please confirm your email to unlock your full Dar El Jamila experience.') }}</p>
        </div>

        @if (session('status') == 'verification-link-sent')
            <div class="dj-login-status">{{ __('A new verification link has been sent to the email address you provided during registration.') }}</div>
        @endif

        <p class="dj-login-note">
            {{ __("Didn't receive the email? Check your spam folder, or request a new link below.") }}
        </p>

        <form method="POST" action="{{ route('verification.send') }}" id="dj-verify-form">
            @csrf
            <button type="submit" class="dj-login-submit" id="dj-verify-submit">
                <span id="dj-verify-submit-label">{{ __('Resend Verification Email') }}</span>
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="dj-login-back">← {{ __('Log Out') }}</button>
        </form>
    </div>

    <script>
        (function () {
            var form = document.getElementById('dj-verify-form');
            var submitBtn = document.getElementById('dj-verify-submit');
            var submitLabel = document.getElementById('dj-verify-submit-label');
            form.addEventListener('submit', function () {
                submitBtn.disabled = true;
                submitLabel.innerHTML = '<span class="dj-login-spinner" aria-hidden="true"></span>{{ __('Resend Verification Email') }}';
            });
        })();
    </script>
</body>
</html>
