<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Forgot Password') }} — Dar El Jamila</title>
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
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/><path d="M12 14v2.5"/></svg>
        </div>

        <div class="dj-login-heading">
            <h1>{{ __('Forgot Your Password?') }}</h1>
            <p>{{ __('No problem. Enter your email and we will send you a link to reset it.') }}</p>
        </div>

        @if (session('status'))
            <div class="dj-login-status">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" id="dj-forgot-form">
            @csrf

            <div class="dj-field">
                <svg class="dj-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 6.5A2.5 2.5 0 0 1 5.5 4h13A2.5 2.5 0 0 1 21 6.5v11a2.5 2.5 0 0 1-2.5 2.5h-13A2.5 2.5 0 0 1 3 17.5v-11Z"/><path d="m4 6 8 6 8-6"/></svg>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder=" ">
                <label for="email" class="dj-field-label">{{ __('Email') }}</label>
                @error('email')
                    <p class="dj-field-error">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="dj-login-submit" id="dj-forgot-submit">
                <span id="dj-forgot-submit-label">{{ __('Email Password Reset Link') }}</span>
            </button>

            <p class="dj-login-switch">
                {{ __('Remember your password?') }}
                <a href="{{ route('login') }}">{{ __('Log in') }}</a>
            </p>
        </form>
    </div>

    <a href="{{ route('home') }}" class="dj-login-back">← {{ __('Back to Store') }}</a>

    <script>
        (function () {
            var form = document.getElementById('dj-forgot-form');
            var submitBtn = document.getElementById('dj-forgot-submit');
            var submitLabel = document.getElementById('dj-forgot-submit-label');
            form.addEventListener('submit', function () {
                submitBtn.disabled = true;
                submitLabel.innerHTML = '<span class="dj-login-spinner" aria-hidden="true"></span>{{ __('Email Password Reset Link') }}';
            });
        })();
    </script>
</body>
</html>
