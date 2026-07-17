@php
    $metaPixelId = \App\Models\Setting::get('meta_pixel_id');
    $tiktokPixelId = \App\Models\Setting::get('tiktok_pixel_id');
    $ga4MeasurementId = \App\Models\Setting::get('ga4_measurement_id');
@endphp

{{-- Inline (not resources/js/app.js) so this ships the moment this file
     reaches production via a plain git pull — this deploy process has
     repeatedly been observed running for a while on a compiled asset
     bundle that predates the current commit, with no npm run build in
     between (WhatsApp buttons, shop size-filter chips, navbar search,
     back-in-stock notify — same reasoning each time). Each platform's
     tracking code is entirely absent from the page (not just inactive)
     when its Setting is empty — that's the enable/disable mechanism,
     same convention as whatsapp_number. No customer PII (name, email,
     phone, address) is ever passed in any event — only product/order
     ids, names, prices, quantities, and currency. --}}

@if ($metaPixelId)
    <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', @json($metaPixelId));
    fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none"
        src="https://www.facebook.com/tr?id={{ urlencode($metaPixelId) }}&ev=PageView&noscript=1"
    /></noscript>
@endif

@if ($tiktokPixelId)
    <script>
    !function (w, d, t) {
        w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie","holdConsent","revokeConsent","grantConsent"],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<e.length;n++)ttq.setAndDefer(e,e[n]);return e},ttq.load=function(e,n){var r="https://analytics.tiktok.com/i18n/pixel/events.js",o=n&&n.partner;ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=r,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};n=document.createElement("script");n.type="text/javascript",n.async=!0,n.src=r+"?sdkid="+e+"&lib="+t;e=document.getElementsByTagName("script")[0];e.parentNode.insertBefore(n,e)};

        ttq.load(@json($tiktokPixelId));
        ttq.page();
    }(window, document, 'ttq');
    </script>
@endif

@if ($ga4MeasurementId)
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ urlencode($ga4MeasurementId) }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', @json($ga4MeasurementId));
    </script>
@endif

<script>
    /**
     * Single dispatcher for the four e-commerce events this site fires
     * (view_item, add_to_cart, begin_checkout, purchase), translating to
     * each platform's own event name/parameter shape:
     *   Meta:   ViewContent / AddToCart / InitiateCheckout / Purchase
     *   TikTok: ViewContent / AddToCart / InitiateCheckout / CompletePayment
     *   GA4:    view_item   / add_to_cart / begin_checkout  / purchase
     * No-ops per platform when that platform's base snippet isn't loaded
     * (fbq/ttq/gtag undefined), so pages work identically whether 0, 1, 2,
     * or all 3 platforms are configured.
     *
     * data shape: { id, name, price, quantity, value, transactionId, items:
     * [{ id, name, price, quantity }] }. `items`+`value` are used for
     * begin_checkout/purchase (whole cart); `id`+`name`+`price` for a
     * single product on view_item/add_to_cart.
     */
    window.djTrack = function (eventName, data) {
        data = data || {};
        var currency = 'EGP';
        var items = data.items || (data.id != null ? [{ id: data.id, name: data.name, price: data.price, quantity: data.quantity || 1 }] : []);
        var value = data.value != null ? data.value : items.reduce(function (sum, i) { return sum + (i.price || 0) * (i.quantity || 1); }, 0);

        if (window.fbq) {
            var metaEvent = { view_item: 'ViewContent', add_to_cart: 'AddToCart', begin_checkout: 'InitiateCheckout', purchase: 'Purchase' }[eventName];
            if (metaEvent) {
                var metaParams = {
                    currency: currency,
                    value: value,
                    content_type: 'product',
                    content_ids: items.map(function (i) { return i.id; }),
                    contents: items.map(function (i) { return { id: i.id, quantity: i.quantity || 1, item_price: i.price }; }),
                };
                if (items.length === 1) metaParams.content_name = items[0].name;
                fbq('track', metaEvent, metaParams);
            }
        }

        if (window.ttq) {
            var tiktokEvent = { view_item: 'ViewContent', add_to_cart: 'AddToCart', begin_checkout: 'InitiateCheckout', purchase: 'CompletePayment' }[eventName];
            if (tiktokEvent) {
                ttq.track(tiktokEvent, {
                    contents: items.map(function (i) { return { content_id: i.id, content_name: i.name, quantity: i.quantity || 1, price: i.price }; }),
                    value: value,
                    currency: currency,
                });
            }
        }

        if (window.gtag) {
            var gaParams = {
                currency: currency,
                value: value,
                items: items.map(function (i) { return { item_id: i.id, item_name: i.name, price: i.price, quantity: i.quantity || 1 }; }),
            };
            if (data.transactionId) gaParams.transaction_id = data.transactionId;
            gtag('event', eventName, gaParams);
        }
    };

    // Fired by djAddToCart() (resources/js/app.js) on every successful
    // add-to-cart, from any of the site's three entry points (product
    // detail page, product-card quick-add, quick-view modal) — kept as a
    // generic custom-event hook rather than putting platform-specific
    // tracking code in app.js itself, so this stays deploy-proof.
    document.addEventListener('dj:cart-add', function (e) {
        window.djTrack('add_to_cart', e.detail);
    });
</script>
