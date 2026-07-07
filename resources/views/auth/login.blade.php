<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Log in') }} — Dar El-Jamila</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Aref+Ruqaa:wght@400;700&family=Tajawal:wght@300;400;500;700;900&family=Playfair+Display:ital,wght@0,500;0,700;1,500&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="preload" as="image" href="https://images.unsplash.com/photo-1772474528936-4f1187eb1611?w=1600&q=85&auto=format&fit=crop">
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
            <div class="dj-login-mark">{{ __('Dar El-Jamila') }}</div>
            <div class="dj-login-tagline">{{ __('Timeless Elegance. Crafted for You.') }}</div>
        </div>

        <div class="dj-login-heading">
            <h1>{{ __('Welcome Back') }}</h1>
            <p>{{ __('Sign in to continue your journey with us.') }}</p>
        </div>

        @if (session('status'))
            <div class="dj-login-status">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}" id="dj-login-form">
            @csrf

            <div class="dj-field">
                <svg class="dj-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 6.5A2.5 2.5 0 0 1 5.5 4h13A2.5 2.5 0 0 1 21 6.5v11a2.5 2.5 0 0 1-2.5 2.5h-13A2.5 2.5 0 0 1 3 17.5v-11Z"/><path d="m4 6 8 6 8-6"/></svg>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder=" ">
                <label for="email" class="dj-field-label">{{ __('Email') }}</label>
                @error('email')
                    <p class="dj-field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="dj-field dj-has-toggle">
                <svg class="dj-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                <input id="password" type="password" name="password" required autocomplete="current-password" placeholder=" ">
                <label for="password" class="dj-field-label">{{ __('Password') }}</label>
                <button type="button" class="dj-field-toggle" id="dj-toggle-password" aria-label="{{ __('Show password') }}" aria-pressed="false">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" width="100%" height="100%"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
                @error('password')
                    <p class="dj-field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="dj-login-row">
                <label class="dj-checkbox" for="remember_me">
                    <input id="remember_me" type="checkbox" name="remember">
                    <span class="dj-box"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6 9 17l-5-5"/></svg></span>
                    {{ __('Remember me') }}
                </label>

                @if (Route::has('password.request'))
                    <a class="dj-login-forgot" href="{{ route('password.request') }}">{{ __('Forgot your password?') }}</a>
                @endif
            </div>

            <button type="submit" class="dj-login-submit" id="dj-login-submit">
                <span id="dj-login-submit-label">{{ __('Log in') }}</span>
            </button>

            @if (Route::has('register'))
                <p class="dj-login-switch">
                    {{ __("Don't have an account?") }}
                    <a href="{{ route('register') }}">{{ __('Create Account') }}</a>
                </p>
            @endif
        </form>
    </div>

    <a href="{{ route('home') }}" class="dj-login-back">← {{ __('Back to Store') }}</a>

    <script>
        (function () {
            var toggleBtn = document.getElementById('dj-toggle-password');
            var passwordInput = document.getElementById('password');
            var showIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" width="100%" height="100%"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>';
            var hideIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" width="100%" height="100%"><path d="M3 3l18 18M10.6 10.6a2 2 0 0 0 2.8 2.8M9.4 5.6A9.9 9.9 0 0 1 12 5c6.5 0 10 7 10 7a13.6 13.6 0 0 1-3 3.9M6.1 6.9C4 8.3 2 12 2 12s3.5 7 10 7c1.2 0 2.3-.2 3.3-.6"/></svg>';

            toggleBtn.addEventListener('click', function () {
                var showing = passwordInput.type === 'text';
                passwordInput.type = showing ? 'password' : 'text';
                toggleBtn.innerHTML = showing ? showIcon : hideIcon;
                toggleBtn.setAttribute('aria-pressed', (!showing).toString());
                toggleBtn.setAttribute('aria-label', showing ? '{{ __('Show password') }}' : '{{ __('Hide password') }}');
            });

            var form = document.getElementById('dj-login-form');
            var submitBtn = document.getElementById('dj-login-submit');
            var submitLabel = document.getElementById('dj-login-submit-label');
            form.addEventListener('submit', function () {
                submitBtn.disabled = true;
                submitLabel.innerHTML = '<span class="dj-login-spinner" aria-hidden="true"></span>{{ __('Log in') }}';
            });
        })();
    </script>
</body>
</html>
