<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', config('app.name', 'Dar El Jamila'))</title>

        @include('partials.favicon-links')

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-stone-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-stone-50">

            <div class="w-full max-w-md flex items-center justify-between px-6 sm:px-0 mb-4">
                <a href="{{ route('home') }}"><x-brand-logo variant="light" style="height:40px;width:auto;" /></a>

                <div class="flex items-center gap-1 text-sm text-stone-400">
                    <span aria-hidden="true">🌐</span>
                    <a href="{{ route('lang.switch', 'en') }}" class="hover:text-stone-700 {{ app()->getLocale() === 'en' ? 'text-stone-900 font-semibold' : '' }}">EN</a>
                    /
                    <a href="{{ route('lang.switch', 'ar') }}" class="hover:text-stone-700 {{ app()->getLocale() === 'ar' ? 'text-stone-900 font-semibold' : '' }}">عربي</a>
                </div>
            </div>

            <div class="w-full sm:max-w-md mt-2 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg border border-stone-200">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
