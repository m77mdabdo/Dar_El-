<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Create Account') }} — Dar El Jamila</title>
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

    <div class="dj-login-card dj-wide">
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

        <div class="dj-login-heading">
            <h1>{{ __('Create Your Account') }}</h1>
            <p>{{ __('Join us for a more personal shopping experience.') }}</p>
        </div>

        @if (session('status'))
            <div class="dj-login-status">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('register') }}" id="dj-register-form">
            @csrf

            <div class="dj-field">
                <svg class="dj-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 12a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9Z"/><path d="M4 20.5c1.4-3.6 4.6-5.5 8-5.5s6.6 1.9 8 5.5"/></svg>
                <input id="name" type="text" name="name" value="{{ old('name', request()->query('name', '')) }}" required autofocus autocomplete="name" placeholder=" ">
                <label for="name" class="dj-field-label">{{ __('Full Name') }}</label>
                @error('name')
                    <p class="dj-field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="dj-field">
                <svg class="dj-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 6.5A2.5 2.5 0 0 1 5.5 4h13A2.5 2.5 0 0 1 21 6.5v11a2.5 2.5 0 0 1-2.5 2.5h-13A2.5 2.5 0 0 1 3 17.5v-11Z"/><path d="m4 6 8 6 8-6"/></svg>
                <input id="email" type="email" name="email" value="{{ old('email', request()->query('email', '')) }}" required autocomplete="username" placeholder=" ">
                <label for="email" class="dj-field-label">{{ __('Email') }}</label>
                @error('email')
                    <p class="dj-field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="dj-field">
                <svg class="dj-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M6.6 10.8c1.4 2.8 3.8 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C10.6 21 3 13.4 3 4c0-.6.4-1 1-1h3.4c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.4 0 .8-.2 1L6.6 10.8Z"/></svg>
                <input id="phone" type="tel" name="phone" value="{{ old('phone', request()->query('phone', '')) }}" required autocomplete="tel" placeholder=" ">
                <label for="phone" class="dj-field-label">{{ __('Phone Number') }}</label>
                @error('phone')
                    <p class="dj-field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="dj-field dj-has-toggle">
                <svg class="dj-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                <input id="password" type="password" name="password" required autocomplete="new-password" placeholder=" ">
                <label for="password" class="dj-field-label">{{ __('Password') }}</label>
                <button type="button" class="dj-field-toggle dj-toggle-password" data-target="password" aria-label="{{ __('Show password') }}" aria-pressed="false">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" width="100%" height="100%"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
                @error('password')
                    <p class="dj-field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="dj-field dj-has-toggle">
                <svg class="dj-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" placeholder=" ">
                <label for="password_confirmation" class="dj-field-label">{{ __('Confirm Password') }}</label>
                <button type="button" class="dj-field-toggle dj-toggle-password" data-target="password_confirmation" aria-label="{{ __('Show password') }}" aria-pressed="false">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" width="100%" height="100%"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
                @error('password_confirmation')
                    <p class="dj-field-error">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="dj-login-submit" id="dj-register-submit">
                <span id="dj-register-submit-label">{{ __('Create Account') }}</span>
            </button>

            <p class="dj-login-switch">
                {{ __('Already have an account?') }}
                <a href="{{ route('login') }}">{{ __('Log in') }}</a>
            </p>
        </form>

        @include('auth.partials.social-login', ['googleLabel' => __('Sign up with Google')])
    </div>

    <a href="{{ route('home') }}" class="dj-login-back">← {{ __('Back to Store') }}</a>

    <script>
        (function () {
            var showIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" width="100%" height="100%"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>';
            var hideIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" width="100%" height="100%"><path d="M3 3l18 18M10.6 10.6a2 2 0 0 0 2.8 2.8M9.4 5.6A9.9 9.9 0 0 1 12 5c6.5 0 10 7 10 7a13.6 13.6 0 0 1-3 3.9M6.1 6.9C4 8.3 2 12 2 12s3.5 7 10 7c1.2 0 2.3-.2 3.3-.6"/></svg>';

            document.querySelectorAll('.dj-toggle-password').forEach(function (toggleBtn) {
                var input = document.getElementById(toggleBtn.dataset.target);
                toggleBtn.addEventListener('click', function () {
                    var showing = input.type === 'text';
                    input.type = showing ? 'password' : 'text';
                    toggleBtn.innerHTML = showing ? showIcon : hideIcon;
                    toggleBtn.setAttribute('aria-pressed', (!showing).toString());
                    toggleBtn.setAttribute('aria-label', showing ? '{{ __('Show password') }}' : '{{ __('Hide password') }}');
                });
            });

            var form = document.getElementById('dj-register-form');
            var submitBtn = document.getElementById('dj-register-submit');
            var submitLabel = document.getElementById('dj-register-submit-label');
            form.addEventListener('submit', function () {
                submitBtn.disabled = true;
                submitLabel.innerHTML = '<span class="dj-login-spinner" aria-hidden="true"></span>{{ __('Create Account') }}';
            });
        })();
    </script>
</body>
</html>
