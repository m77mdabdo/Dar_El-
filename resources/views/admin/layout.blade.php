<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('admin.dashboard.title')) — {{ __('admin.brand') }}</title>
    @include('partials.favicon-links')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Aref+Ruqaa:wght@400;700&family=Tajawal:wght@300;400;500;700;900&family=Playfair+Display:ital,wght@0,500;0,700;1,500&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    {{-- Loaded before admin.js so its alpine:init listener attaches before Alpine.start() fires. --}}
    @if (request()->routeIs('admin.products.*'))
        @vite(['resources/js/admin-products.js'])
    @endif
    @vite(['resources/css/app.css', 'resources/js/admin.js'])
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="dj-admin-shell antialiased overflow-x-hidden {{ app()->getLocale() === 'en' ? 'dj-en' : '' }}">
    @php $rtl = app()->getLocale() === 'ar'; @endphp
    <div
        class="flex min-h-screen"
        x-data="{ sidebarOpen: false, notifOpen: false, profileOpen: false }"
        @keydown.escape.window="notifOpen = false; profileOpen = false"
    >
        <div
            x-show="sidebarOpen"
            x-cloak
            @click="sidebarOpen = false"
            class="fixed inset-0 bg-[rgba(42,16,21,.55)] z-30 min-[1024px]:hidden"
        ></div>

        <aside
            :class="sidebarOpen ? 'translate-x-0' : '{{ $rtl ? 'translate-x-full' : '-translate-x-full' }}'"
            class="dj-admin-sidebar w-72 min-[1024px]:w-64 shrink-0 fixed min-[1024px]:relative inset-y-0 {{ $rtl ? 'right-0' : 'left-0' }} z-40 transition-transform duration-200 min-[1024px]:translate-x-0 overflow-y-auto dj-admin-sidebar-scroll"
        >
            <div class="dj-admin-brand-block sticky top-0 bg-transparent">
                <a href="{{ route('admin.dashboard') }}" class="dj-admin-brand-mark">
                    <x-brand-logo class="dj-admin-logo" />
                </a>
            </div>

            @include('admin.partials.sidebar')

            <div class="dj-admin-sidebar-footer">
                <a href="{{ route('home') }}">
                    <span aria-hidden="true">{{ $rtl ? '→' : '←' }}</span> {{ __('admin.back_to_store') }}
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full">{{ __('admin.log_out') }}</button>
                </form>
            </div>
        </aside>

        <div class="flex-1 min-w-0 flex flex-col">
            <header class="dj-admin-topbar px-3 sm:px-6 py-3 flex items-center gap-2 sm:gap-4">
                <button @click="sidebarOpen = !sidebarOpen" class="dj-admin-icon-btn min-[1024px]:hidden shrink-0 -ms-1" aria-label="{{ __('admin.menu') }}" :aria-expanded="sidebarOpen.toString()">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                    </svg>
                </button>

                <h1 class="text-base sm:text-xl font-semibold shrink-0 truncate max-w-[40vw] sm:max-w-none text-[var(--dj-maroon-dark)]">@yield('title', __('admin.dashboard.title'))</h1>

                <form method="GET" action="{{ route('admin.products.index') }}" class="flex-1 min-w-0 max-w-md hidden sm:block">
                    <label class="dj-admin-search">
                        <span class="sr-only">{{ __('admin.search') }}</span>
                        <svg class="absolute {{ $rtl ? 'right-3.5' : 'left-3.5' }} top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--dj-rose-dust)] pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m21 21-4.3-4.3"/></svg>
                        <input
                            type="search" name="search" placeholder="{{ __('admin.search_placeholder') }}"
                            class="{{ $rtl ? 'pe-10' : 'ps-10' }}"
                        >
                    </label>
                </form>

                <div class="flex items-center gap-1.5 sm:gap-3 text-sm ms-auto shrink-0">
                    <div class="dj-admin-lang-pill hidden md:inline-flex">
                        <a href="{{ route('lang.switch', 'en') }}" class="{{ app()->getLocale() === 'en' ? 'dj-active' : '' }}">EN</a>
                        <a href="{{ route('lang.switch', 'ar') }}" class="{{ app()->getLocale() === 'ar' ? 'dj-active' : '' }}">عربي</a>
                    </div>

                    {{-- Notification bell --}}
                    <div class="relative">
                        <button
                            @click="notifOpen = !notifOpen; profileOpen = false"
                            class="dj-admin-icon-btn"
                            aria-label="{{ __('admin.notifications.title') }}"
                            :aria-expanded="notifOpen.toString()"
                        >
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
                            <span class="dj-admin-notif-badge {{ $rtl ? 'left-1' : 'right-1' }} {{ ($notifUnreadCount ?? 0) > 0 ? '' : 'hidden' }}">
                                {{ min($notifUnreadCount ?? 0, 99) }}{{ ($notifUnreadCount ?? 0) > 99 ? '+' : '' }}
                            </span>
                        </button>

                        <div
                            x-show="notifOpen" x-cloak @click.outside="notifOpen = false"
                            x-transition
                            class="dj-admin-dropdown absolute {{ $rtl ? 'left-0' : 'right-0' }} mt-2 w-[90vw] max-w-sm z-20"
                        >
                            <div class="dj-admin-dropdown-head">
                                <span>{{ __('admin.notifications.title') }}</span>
                                @if (($notifUnreadCount ?? 0) > 0)
                                    <button type="button" onclick="adminMarkAllNotificationsRead('{{ route('admin.notifications.read-all') }}')" class="text-[11px] font-bold text-[var(--dj-maroon)] hover:underline">{{ __('admin.notifications.mark_all_read') }}</button>
                                @endif
                            </div>
                            <div class="max-h-80 overflow-y-auto">
                                @forelse ($notifRecent ?? [] as $notification)
                                    @include('admin.partials.notification-item', ['notification' => $notification])
                                @empty
                                    <div class="dj-admin-empty">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
                                        <p class="text-sm">{{ __('admin.notifications.empty') }}</p>
                                    </div>
                                @endforelse
                            </div>
                            <a href="{{ route('admin.notifications.index') }}" class="dj-admin-dropdown-link">{{ __('admin.view_all') }}</a>
                        </div>
                    </div>

                    {{-- Profile dropdown --}}
                    <div class="relative">
                        <button
                            @click="profileOpen = !profileOpen; notifOpen = false"
                            class="flex items-center gap-2 px-1 sm:px-1.5 py-1 rounded-full hover:bg-[var(--dj-cream)] transition-colors"
                            :aria-expanded="profileOpen.toString()"
                        >
                            <span class="dj-admin-avatar">{{ Str::of(Auth::user()->name)->substr(0, 1)->upper() }}</span>
                            <span class="hidden lg:block text-[var(--dj-ink)] font-medium truncate max-w-[120px]">{{ Auth::user()->name }}</span>
                        </button>

                        <div
                            x-show="profileOpen" x-cloak @click.outside="profileOpen = false"
                            x-transition
                            class="dj-admin-dropdown dj-admin-profile-menu absolute {{ $rtl ? 'left-0' : 'right-0' }} mt-2 w-48 z-20 py-1"
                        >
                            <a href="{{ route('admin.profile.edit') }}">{{ __('admin.profile.title') }}</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit">{{ __('admin.log_out') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <main class="p-3 sm:p-6 flex-1 min-w-0">
                @if (session('status'))
                    <span class="hidden" data-flash-toast="success">{{ session('status') }}</span>
                @endif
                @if (session('error'))
                    <span class="hidden" data-flash-toast="error">{{ session('error') }}</span>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
