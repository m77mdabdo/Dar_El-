<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('admin.dashboard.title')) — {{ __('admin.brand') }}</title>
    @vite(['resources/css/app.css', 'resources/js/admin.js'])
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="font-sans antialiased bg-stone-100 text-stone-900 overflow-x-hidden">
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
            class="fixed inset-0 bg-stone-900/50 z-30 min-[1024px]:hidden"
        ></div>

        <aside
            :class="sidebarOpen ? 'translate-x-0' : '{{ $rtl ? 'translate-x-full' : '-translate-x-full' }}'"
            class="w-72 min-[1024px]:w-64 bg-stone-900 text-stone-300 shrink-0 fixed min-[1024px]:static inset-y-0 {{ $rtl ? 'right-0' : 'left-0' }} z-40 transition-transform duration-200 min-[1024px]:translate-x-0 overflow-y-auto"
        >
            <div class="p-4 text-white font-serif text-lg border-b border-stone-800 sticky top-0 bg-stone-900 z-10">{{ __('admin.brand') }}</div>

            @include('admin.partials.sidebar')

            <div class="p-2 mt-2 mb-4 border-t border-stone-800 space-y-0.5">
                <a href="{{ route('home') }}" class="flex items-center gap-2 px-3 py-2.5 text-xs text-stone-400 hover:bg-stone-800 hover:text-white rounded-lg">
                    <span aria-hidden="true">{{ $rtl ? '→' : '←' }}</span> {{ __('admin.back_to_store') }}
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="block w-full text-start px-3 py-2.5 text-xs text-stone-400 hover:bg-stone-800 hover:text-white rounded-lg">{{ __('admin.log_out') }}</button>
                </form>
            </div>
        </aside>

        <div class="flex-1 min-w-0 flex flex-col">
            <header class="bg-white border-b border-stone-200 px-3 sm:px-6 py-3 flex items-center gap-2 sm:gap-4">
                <button @click="sidebarOpen = !sidebarOpen" class="min-[1024px]:hidden w-10 h-10 flex items-center justify-center shrink-0 -ms-1" aria-label="{{ __('admin.menu') }}" :aria-expanded="sidebarOpen.toString()">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                    </svg>
                </button>

                <h1 class="text-base sm:text-xl font-semibold shrink-0 truncate max-w-[40vw] sm:max-w-none">@yield('title', __('admin.dashboard.title'))</h1>

                <form method="GET" action="{{ route('admin.products.index') }}" class="flex-1 min-w-0 max-w-md hidden sm:block">
                    <label class="relative block">
                        <span class="sr-only">{{ __('admin.search') }}</span>
                        <svg class="absolute {{ $rtl ? 'right-3' : 'left-3' }} top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m21 21-4.3-4.3"/></svg>
                        <input
                            type="search" name="search" placeholder="{{ __('admin.search_placeholder') }}"
                            class="w-full text-sm rounded-full border-stone-300 {{ $rtl ? 'pe-9' : 'ps-9' }} py-2"
                        >
                    </label>
                </form>

                <div class="flex items-center gap-1.5 sm:gap-3 text-sm ms-auto shrink-0">
                    <div class="hidden md:flex items-center gap-1 text-stone-400 text-xs">
                        <span aria-hidden="true">🌐</span>
                        <a href="{{ route('lang.switch', 'en') }}" class="hover:text-stone-700 {{ app()->getLocale() === 'en' ? 'text-stone-900 font-semibold' : '' }}">EN</a>
                        /
                        <a href="{{ route('lang.switch', 'ar') }}" class="hover:text-stone-700 {{ app()->getLocale() === 'ar' ? 'text-stone-900 font-semibold' : '' }}">عربي</a>
                    </div>

                    {{-- Notification bell --}}
                    <div class="relative">
                        <button
                            @click="notifOpen = !notifOpen; profileOpen = false"
                            class="relative w-10 h-10 flex items-center justify-center rounded-full hover:bg-stone-100"
                            aria-label="{{ __('admin.notifications.title') }}"
                            :aria-expanded="notifOpen.toString()"
                        >
                            <svg class="w-5 h-5 text-stone-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
                            <span class="dj-admin-notif-badge absolute top-1.5 {{ $rtl ? 'left-1.5' : 'right-1.5' }} min-w-[16px] h-4 px-1 rounded-full bg-rose-600 text-white text-[10px] leading-4 text-center {{ ($notifUnreadCount ?? 0) > 0 ? '' : 'hidden' }}">
                                {{ min($notifUnreadCount ?? 0, 99) }}{{ ($notifUnreadCount ?? 0) > 99 ? '+' : '' }}
                            </span>
                        </button>

                        <div
                            x-show="notifOpen" x-cloak @click.outside="notifOpen = false"
                            x-transition
                            class="absolute {{ $rtl ? 'left-0' : 'right-0' }} mt-2 w-[90vw] max-w-sm bg-white border border-stone-200 rounded-lg shadow-lg z-20 overflow-hidden"
                        >
                            <div class="flex items-center justify-between px-4 py-3 border-b border-stone-100">
                                <span class="font-medium text-sm">{{ __('admin.notifications.title') }}</span>
                                @if (($notifUnreadCount ?? 0) > 0)
                                    <button type="button" onclick="adminMarkAllNotificationsRead('{{ route('admin.notifications.read-all') }}')" class="text-xs text-rose-700 hover:underline">{{ __('admin.notifications.mark_all_read') }}</button>
                                @endif
                            </div>
                            <div class="max-h-80 overflow-y-auto">
                                @forelse ($notifRecent ?? [] as $notification)
                                    @include('admin.partials.notification-item', ['notification' => $notification])
                                @empty
                                    <p class="px-4 py-8 text-center text-sm text-stone-400">{{ __('admin.notifications.empty') }}</p>
                                @endforelse
                            </div>
                            <a href="{{ route('admin.notifications.index') }}" class="block text-center text-xs font-medium text-rose-700 hover:bg-stone-50 px-4 py-3 border-t border-stone-100">{{ __('admin.view_all') }}</a>
                        </div>
                    </div>

                    {{-- Profile dropdown --}}
                    <div class="relative">
                        <button
                            @click="profileOpen = !profileOpen; notifOpen = false"
                            class="flex items-center gap-2 px-1.5 sm:px-2 py-1.5 rounded-full hover:bg-stone-100"
                            :aria-expanded="profileOpen.toString()"
                        >
                            <span class="w-8 h-8 rounded-full bg-rose-700 text-white flex items-center justify-center text-xs font-semibold shrink-0">
                                {{ Str::of(Auth::user()->name)->substr(0, 1)->upper() }}
                            </span>
                            <span class="hidden lg:block text-stone-700 truncate max-w-[120px]">{{ Auth::user()->name }}</span>
                        </button>

                        <div
                            x-show="profileOpen" x-cloak @click.outside="profileOpen = false"
                            x-transition
                            class="absolute {{ $rtl ? 'left-0' : 'right-0' }} mt-2 w-48 bg-white border border-stone-200 rounded-lg shadow-lg z-20 py-1 text-sm"
                        >
                            <a href="{{ route('admin.profile.edit') }}" class="block px-4 py-2 hover:bg-stone-50">{{ __('admin.profile.title') }}</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="w-full text-start px-4 py-2 hover:bg-stone-50">{{ __('admin.log_out') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <main class="p-3 sm:p-6 flex-1 min-w-0">
                @if (session('status'))
                    <div class="bg-green-50 text-green-800 border border-green-200 rounded px-4 py-3 text-sm mb-4">{{ session('status') }}</div>
                @endif
                @if (session('error'))
                    <div class="bg-red-50 text-red-800 border border-red-200 rounded px-4 py-3 text-sm mb-4">{{ session('error') }}</div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
