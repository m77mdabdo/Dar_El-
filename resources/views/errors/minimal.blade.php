<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') — {{ config('app.name', 'Dar El Jamila') }}</title>
    @include('partials.favicon-links')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Aref+Ruqaa:wght@400;700&family=Tajawal:wght@300;400;500;700;900&family=Playfair+Display:ital,wght@0,500;0,700;1,500&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    {{--
        Deliberately no @vite() / no app.css dependency — this page must
        still render something coherent even in a degraded state (a build
        gone missing, a 500 triggered by an asset-manifest problem), so
        every style needed is inlined here.
    --}}
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
            background: radial-gradient(ellipse at center, #601526, #3C0B17 75%);
            font-family: {{ app()->getLocale() === 'ar' ? "'Tajawal', sans-serif" : "'Poppins', sans-serif" }};
            color: #F7EFE4; text-align: center; padding: 24px;
        }
        .dj-error-wrap { max-width: 480px; }
        .dj-error-logo { width: clamp(150px, 18vw, 190px); max-width: 100%; height: auto; margin: 0 auto 32px; display: block; }
        .dj-error-code { font-size: 15px; letter-spacing: 4px; color: #E8C39A; text-transform: uppercase; margin: 0 0 14px; font-weight: 600; }
        .dj-error-title { font-family: {{ app()->getLocale() === 'ar' ? "'Aref Ruqaa', serif" : "'Playfair Display', serif" }}; font-size: 30px; margin: 0 0 14px; color: #F7EFE4; }
        .dj-error-message { font-size: 15px; line-height: 1.8; color: #E0C9C2; margin: 0 0 32px; }
        .dj-error-link {
            display: inline-flex; align-items: center; gap: 8px; color: #3C0B17; background: #E8C39A;
            padding: 12px 28px; border-radius: 30px; text-decoration: none; font-size: 14px; font-weight: 700;
            letter-spacing: .5px; transition: opacity .2s;
        }
        .dj-error-link:hover { opacity: .85; }
    </style>
</head>
<body>
    <div class="dj-error-wrap">
        <img src="{{ asset('assets/branding/logo-transparent.svg') }}" alt="{{ __('Dar El Jamila') }}" class="dj-error-logo">
        <p class="dj-error-code">@yield('code')</p>
        <h1 class="dj-error-title">@yield('title')</h1>
        <p class="dj-error-message">@yield('message')</p>
        <a href="{{ url('/') }}" class="dj-error-link">{{ __('general.errors.back_home') }}</a>
    </div>
</body>
</html>
