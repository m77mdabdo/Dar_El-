{{-- Shared browser-icon block — include once in every full <head> (storefront, admin, each standalone auth page, error pages).
     favicon.ico lives at the site ROOT (not under assets/branding) — browsers
     and crawlers (including Google's favicon fetcher) fall back to /favicon.ico
     by convention regardless of the <link> tags below, so it has to exist
     there to be found reliably. --}}
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<link rel="icon" type="image/svg+xml" href="{{ asset('assets/branding/favicon.svg') }}">
<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/branding/favicon-16x16.png') }}">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/branding/favicon-32.png') }}">
<link rel="icon" type="image/png" sizes="192x192" href="{{ asset('assets/branding/favicon-192.png') }}">
<link rel="apple-touch-icon" href="{{ asset('assets/branding/apple-touch-icon.png') }}">
<link rel="manifest" href="{{ asset('site.webmanifest') }}">
<meta name="theme-color" content="#3C0B17">
