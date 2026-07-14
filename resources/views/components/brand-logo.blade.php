@props(['variant' => 'dark'])

{{--
    Single source of truth for the Dar El Jamila logo everywhere in the app.
    variant="dark"  -> logo-transparent.svg (gold-on-transparent; for dark/maroon surfaces: nav, footer, admin sidebar, splash screen)
    variant="light" -> logo-on-light-bg.svg (carries its own dark plate; for white/light surfaces: auth cards, light admin panels)
    Intrinsic 520x200 (2.6:1) — only width or height should ever be set via $attributes so the browser preserves aspect ratio; never force both.
--}}
@php
    $src = $variant === 'light'
        ? asset('assets/branding/logo-on-light-bg.svg')
        : asset('assets/branding/logo-transparent.svg');
@endphp
<img
    src="{{ $src }}"
    alt="{{ __('Dar El Jamila') }}"
    {{ $attributes->merge(['class' => 'dj-brand-logo', 'width' => 520, 'height' => 200, 'loading' => 'lazy', 'decoding' => 'async']) }}
>
