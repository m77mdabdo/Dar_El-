/* =========================================================
   Dar El Jamila — Service Worker
   Deliberately conservative for a live e-commerce site: static,
   content-hashed build assets are cached aggressively, but anything
   involving price, stock, cart, checkout, account, or admin data is
   always fetched fresh from the network and NEVER cached. See the
   route tables below before changing what falls into which bucket.

   Must live at the site root (not under /build) so its default scope
   covers the whole origin — see production deploy notes in the repo
   memory about this file needing a manual copy to the separate
   public_html/ webroot, same as favicon.ico/robots.txt.
   ========================================================= */

const SW_VERSION = 'v1';
const STATIC_CACHE = `dj-static-${SW_VERSION}`;
const RUNTIME_CACHE = `dj-runtime-${SW_VERSION}`;
const FONT_CACHE = `dj-fonts-${SW_VERSION}`;
const CURRENT_CACHES = [STATIC_CACHE, RUNTIME_CACHE, FONT_CACHE];

const OFFLINE_URL = '/offline';

// Fetched once at install time (while definitely online) so the offline
// fallback page can render fully — including its logo — with zero network
// dependency later.
const PRECACHE_URLS = [
    OFFLINE_URL,
    '/assets/branding/logo-transparent.svg',
];

// Paths that must ALWAYS hit the network: prices, stock, cart, checkout,
// account/order data, admin, auth, and anything CSRF-token-bearing. The
// service worker does not call event.respondWith() for these at all — the
// browser performs its normal, uninterrupted fetch, exactly as if this
// worker didn't exist. Non-GET requests (see fetch handler below) are
// excluded the same way regardless of path.
const NEVER_CACHE_PREFIXES = [
    '/admin',
    '/checkout',
    '/cart',
    '/account',
    '/login',
    '/register',
    '/logout',
    '/forgot-password',
    '/reset-password',
    '/confirm-password',
    '/verify-email',
    '/verify-otp',
    '/resend-otp',
    '/auth/',
    '/profile',
    '/dashboard',
    '/invoice/',
    '/track-order',
    '/notify-me/',
    '/search/live',
    '/lang/',
];

function isNeverCachePath(pathname) {
    return NEVER_CACHE_PREFIXES.some((prefix) => pathname === prefix || pathname.startsWith(prefix));
}

// Truly static, content-hashed or rarely-changing files — safe to serve
// straight from cache and only hit the network to fill/refresh the cache.
function isStaticAsset(pathname) {
    return pathname.startsWith('/build/')
        || pathname.startsWith('/assets/')
        || pathname.startsWith('/storage/')
        || pathname === '/favicon.ico'
        || pathname === '/site.webmanifest'
        || /\.(css|js|woff2?|ttf|eot|png|jpe?g|webp|gif|svg|ico)$/i.test(pathname);
}

function isGoogleFontsHost(hostname) {
    return hostname === 'fonts.googleapis.com' || hostname === 'fonts.gstatic.com';
}

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => cache.addAll(PRECACHE_URLS))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys.filter((key) => !CURRENT_CACHES.includes(key)).map((key) => caches.delete(key))
            ))
            .then(() => self.clients.claim())
    );
});

async function cacheFirst(request, cacheName) {
    const cache = await caches.open(cacheName);
    const cached = await cache.match(request);
    if (cached) return cached;

    const response = await fetch(request);
    if (response && response.ok) {
        await cache.put(request, response.clone());
    }
    return response;
}

// Always tries the network first so prices/stock/layout are as fresh as
// possible; only falls back to a previously-cached copy of this exact page
// (or, failing that, the offline page) when the network genuinely fails —
// never serves stale content when a fresh fetch would have worked.
async function networkFirstNavigation(request) {
    const cache = await caches.open(RUNTIME_CACHE);

    try {
        const response = await fetch(request);
        if (response && response.ok) {
            await cache.put(request, response.clone());
        }
        return response;
    } catch (err) {
        const cached = await cache.match(request);
        if (cached) return cached;

        const offline = await caches.match(OFFLINE_URL);
        if (offline) return offline;

        throw err;
    }
}

self.addEventListener('fetch', (event) => {
    const { request } = event;

    // Mutations are never intercepted — always the browser's normal fetch.
    if (request.method !== 'GET') return;

    const url = new URL(request.url);

    if (url.origin !== self.location.origin) {
        if (isGoogleFontsHost(url.hostname)) {
            event.respondWith(cacheFirst(request, FONT_CACHE));
        }
        return;
    }

    if (isNeverCachePath(url.pathname)) {
        return;
    }

    if (isStaticAsset(url.pathname)) {
        event.respondWith(cacheFirst(request, STATIC_CACHE));
        return;
    }

    // Page shells (home, shop, product/category pages, blog, etc.) — cached
    // only as a same-URL fallback for when the network is unreachable; the
    // offline page is the last resort, and only for real page navigations
    // (never for background/XHR GETs, which should just fail naturally).
    if (request.mode === 'navigate') {
        event.respondWith(networkFirstNavigation(request));
    }
});
