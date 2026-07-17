<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>window.djI18n = { close: @json(__('Close')) };</script>
    @include('partials.tracking-base')
    <meta name="description" content="@yield('meta_description', __('Abayas and dresses crafted with care to highlight your elegance in every occasion.'))">
    <link rel="canonical" href="@yield('canonical', url()->current())">
    <meta property="og:title" content="@yield('title', config('app.name', 'Dar El Jamila'))">
    <meta property="og:description" content="@yield('meta_description', __('Abayas and dresses crafted with care to highlight your elegance in every occasion.'))">
    <meta property="og:image" content="@yield('og_image', asset('assets/branding/favicon-512.png'))">
    <meta property="og:url" content="@yield('canonical', url()->current())">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', config('app.name', 'Dar El Jamila'))">
    <meta name="twitter:description" content="@yield('meta_description', __('Abayas and dresses crafted with care to highlight your elegance in every occasion.'))">
    <meta name="twitter:image" content="@yield('og_image', asset('assets/branding/favicon-512.png'))">
    <title>@yield('title', config('app.name', 'Dar El Jamila'))</title>
    @include('partials.favicon-links')
    @include('partials.organization-schema')
    @include('partials.local-business-schema')
    @yield('structured_data')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Aref+Ruqaa:wght@400;700&family=Tajawal:wght@300;400;500;700;900&family=Playfair+Display:ital,wght@0,500;0,700;1,500&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="dj-storefront min-h-screen flex flex-col {{ app()->getLocale() === 'en' ? 'dj-en' : '' }}">

    <div id="dj-splash"><x-brand-logo class="dj-splash-mark" style="width:220px;height:auto;" /><div class="dj-splash-line"></div></div>
    <div id="dj-nav-progress" aria-hidden="true"></div>
    <div id="dj-scroll-progress"></div>
    <button id="dj-back-to-top" onclick="window.scrollTo({top:0, behavior:'smooth'})" aria-label="{{ __('Back to top') }}">↑</button>

    <div x-data="{ mobileNavOpen: false }">
    <nav class="dj-nav">
        <div class="dj-nav-logo"><a href="{{ route('home') }}"><x-brand-logo class="dj-nav-logo-img" style="height:38px;width:auto;" /></a></div>

        <div class="dj-nav-links">
            <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'dj-active' : '' }}">{{ __('Home') }}</a>
            <a href="{{ route('shop.index') }}" class="{{ request()->routeIs('shop.*') ? 'dj-active' : '' }}">{{ __('Shop') }}</a>
            <a href="{{ route('services') }}" class="{{ request()->routeIs('services') ? 'dj-active' : '' }}">{{ __('Services') }}</a>
            <a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'dj-active' : '' }}">{{ __('About') }}</a>
            <a href="{{ route('blog.index') }}" class="{{ request()->routeIs('blog.*') ? 'dj-active' : '' }}">{{ __('Blog') }}</a>
            <a href="{{ route('contact.show') }}" class="{{ request()->routeIs('contact.*') ? 'dj-active' : '' }}">{{ __('Contact') }}</a>
        </div>

        <div class="dj-nav-right">
            @include('partials.global-search')

            @auth
                <a href="{{ route('wishlist.index') }}" class="dj-cart-btn" aria-label="{{ __('Wishlist') }}">
                    ♡ <span>{{ __('Wishlist') }}</span> <span class="dj-cart-count" id="dj-wishlist-count">{{ $wishlistCount ?? 0 }}</span>
                </a>
            @else
                <a href="{{ route('login', ['redirect' => url()->current()]) }}" class="dj-cart-btn" aria-label="{{ __('Wishlist') }}">
                    ♡ <span>{{ __('Wishlist') }}</span> <span class="dj-cart-count" id="dj-wishlist-count">0</span>
                </a>
            @endauth

            <button type="button" class="dj-cart-btn" onclick="djOpenCart()" aria-label="{{ __('Shopping Cart') }}">
                🛍️ <span>{{ __('Cart') }}</span> <span class="dj-cart-count" id="dj-cart-count">{{ $cartCount ?? 0 }}</span>
            </button>

            <a href="{{ route('lang.switch', app()->getLocale() === 'ar' ? 'en' : 'ar') }}" class="dj-lang-btn">
                🌐 <span>{{ app()->getLocale() === 'ar' ? 'EN' : 'AR' }}</span>
            </a>

            @auth
                <div class="relative hidden sm:block" x-data="{ open: false }">
                    <button @click="open = !open" class="text-cream-2 text-sm hover:text-gold">{{ auth()->user()->name }}</button>
                    <div x-show="open" x-transition @click.outside="open = false" x-cloak class="absolute {{ app()->getLocale() === 'ar' ? 'left-0' : 'right-0' }} mt-2 w-44 bg-white border border-stone-200 rounded shadow-lg py-1 z-10 text-sm text-stone-700">
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 hover:bg-stone-50">{{ __('Profile') }}</a>
                        <a href="{{ route('account.orders.index') }}" class="block px-4 py-2 hover:bg-stone-50">{{ __('My Orders') }}</a>
                        <a href="{{ route('account.reviews.index') }}" class="block px-4 py-2 hover:bg-stone-50">{{ __('reviews.title') }}</a>
                        <a href="{{ route('account.blog-comments.index') }}" class="block px-4 py-2 hover:bg-stone-50">{{ __('blog_comments.your_comments') }}</a>
                        @if (auth()->user()->hasAnyRole(['admin', 'super_admin', 'employee']))
                            <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2 hover:bg-stone-50">{{ __('Admin') }}</a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="w-full text-left px-4 py-2 hover:bg-stone-50">{{ __('Log Out') }}</button>
                        </form>
                    </div>
                </div>
            @else
                <a href="{{ route('login') }}" class="hidden sm:inline text-cream-2 text-sm hover:text-gold">{{ __('Login') }}</a>
            @endauth

            <button @click="mobileNavOpen = !mobileNavOpen" class="dj-burger" aria-label="{{ __('Menu') }}" :aria-expanded="mobileNavOpen.toString()">☰</button>
        </div>
    </nav>

    <div class="dj-mobile-menu" x-show="mobileNavOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2" x-cloak @click.outside="mobileNavOpen = false">
        <a href="{{ route('home') }}" @click="mobileNavOpen = false">{{ __('Home') }}</a>
        <a href="{{ route('shop.index') }}" @click="mobileNavOpen = false">{{ __('Shop') }}</a>
        <a href="{{ route('services') }}" @click="mobileNavOpen = false">{{ __('Services') }}</a>
        <a href="{{ route('about') }}" @click="mobileNavOpen = false">{{ __('About') }}</a>
        <a href="{{ route('blog.index') }}" @click="mobileNavOpen = false">{{ __('Blog') }}</a>
        <a href="{{ route('contact.show') }}" @click="mobileNavOpen = false">{{ __('Contact') }}</a>
        @auth
            <a href="{{ route('profile.edit') }}" @click="mobileNavOpen = false">{{ __('Profile') }}</a>
            <a href="{{ route('account.orders.index') }}" @click="mobileNavOpen = false">{{ __('My Orders') }}</a>
            <a href="{{ route('account.reviews.index') }}" @click="mobileNavOpen = false">{{ __('reviews.title') }}</a>
            <a href="{{ route('account.blog-comments.index') }}" @click="mobileNavOpen = false">{{ __('blog_comments.your_comments') }}</a>
            @if (auth()->user()->hasAnyRole(['admin', 'super_admin', 'employee']))
                <a href="{{ route('admin.dashboard') }}" @click="mobileNavOpen = false">{{ __('Admin') }}</a>
            @endif
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="w-full text-left">{{ __('Log Out') }}</button>
            </form>
        @else
            <a href="{{ route('login') }}" @click="mobileNavOpen = false">{{ __('Login') }}</a>
        @endauth
    </div>
    </div>

    <main class="flex-1 dj-page-enter">
        @if (session('status'))
            <div class="max-w-3xl mx-auto px-4 sm:px-6 mt-4">
                <div class="bg-cream-2 text-maroon border border-gold/40 rounded-full px-5 py-3 text-sm text-center">{{ session('status') }}</div>
            </div>
        @endif

        @if (session('error'))
            <div class="max-w-3xl mx-auto px-4 sm:px-6 mt-4">
                <div class="bg-rose-dust/10 text-rose-dust border border-rose-dust/30 rounded-full px-5 py-3 text-sm text-center">{{ session('error') }}</div>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="dj-footer">
        <x-brand-logo class="dj-f-logo" style="height:46px;width:auto;margin-inline:auto;" />
        <p>{{ __('Beautiful you are for choosing Dar El Jamila. To order, reach out to us directly via social media or email.') }}</p>

        <form method="POST" action="{{ route('newsletter.store') }}" class="dj-newsletter-form" style="margin-bottom:30px;">
            @csrf
            <input type="email" name="email" required placeholder="{{ __('Your email address') }}" aria-label="{{ __('Your email address') }}">
            <button type="submit">{{ __('Subscribe') }}</button>
        </form>

        <div class="dj-socials">
            @if ($facebook = \App\Models\Setting::get('facebook_url'))
                <a href="{{ $facebook }}" title="Facebook" target="_blank" rel="noopener">FB</a>
            @endif
            @if ($instagram = \App\Models\Setting::get('instagram_url'))
                <a href="{{ $instagram }}" title="Instagram" target="_blank" rel="noopener">IG</a>
            @endif
            @if ($whatsapp = \App\Models\Setting::get('whatsapp_number'))
                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $whatsapp) }}" title="WhatsApp" target="_blank" rel="noopener">WA</a>
            @endif
            <a href="mailto:{{ \App\Models\Setting::get('support_email', 'info@dareljamila.com') }}" title="Email">✉</a>
        </div>

        <a href="{{ route('track-order.form') }}" style="display:inline-block; font-size:12.5px; color:var(--dj-gold); text-decoration:underline; margin-bottom:14px;">{{ __('orders.track_title') }}</a>

        <div class="dj-fine">&copy; {{ date('Y') }} {{ __('Dar El Jamila. All rights reserved.') }}</div>
    </footer>

    @include('partials.cart-drawer')
    @include('partials.product-modal')
    @include('partials.whatsapp-float')

</body>
</html>
