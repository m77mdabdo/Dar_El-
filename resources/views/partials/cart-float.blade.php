{{-- Site-wide floating shortcut to the cart drawer — the ONLY cart entry
     point now that the navbar's own cart button was removed (see
     layouts/storefront.blade.php). Stacks between the WhatsApp float
     (closest to the corner) and #dj-back-to-top (pushed up to make room,
     see app.css). djOpenCart() is a plain global function with no
     dependency on which element called it, so this is genuinely the same
     drawer/behavior the old navbar button opened, not a re-implementation.

     The count badge mirrors what the navbar cart button's own badge used
     to show (#dj-cart-count, same visual language as #dj-wishlist-count) —
     see djUpdateCartCount() in app.js, the one place that now writes to
     it, called from every add/update/remove flow. Starts hidden via the
     hidden attribute (not display:none in CSS) so "0 items" and "count not
     loaded yet" can't be visually confused — the initial value below is
     always a real, server-computed count, never a placeholder.

     .dj-keep-clickable: the install/push-notification bottom banners yield
     (fade out, stop accepting clicks) rather than cover a fixed element
     marked with this class — see djInstallBannerRectOverlaps() in app.js.
     Matches #dj-size-guide-float's own treatment (the other button-style
     float in this stack); WhatsApp's <a> doesn't carry it since it isn't a
     same-page action a banner covering it would actually block.

     Inline (not resources/css/app.css) so this ships the moment this file
     reaches production via a plain git pull — same standing deploy-proofing
     convention as the WhatsApp float right next to it. --}}
<style>
    #dj-cart-float {
        position: fixed; bottom: 92px; right: 26px; width: 56px; height: 56px; border-radius: 50%;
        background: var(--dj-maroon); color: var(--dj-gold); display: flex; align-items: center; justify-content: center;
        box-shadow: var(--dj-shadow); z-index: 85; transition: background .2s, transform .15s; border: none; cursor: pointer;
    }
    #dj-cart-float:hover { background: var(--dj-maroon-dark); transform: scale(1.06); }
    /* 28px, matching the WhatsApp float's icon exactly (not the 26px most
       other outline icons on the site use) — WhatsApp's glyph is a solid
       shape that fills its own viewBox almost edge to edge, so anything
       thinner beside it on the same circle size reads as visually lighter/
       smaller even at an equal pixel count. Sizing up is what actually
       matches its weight. */
    #dj-cart-float svg { width: 28px; height: 28px; }
    body.dj-en #dj-cart-float { right: auto; left: 26px; }

    #dj-cart-float-count {
        position: absolute; top: -3px; right: -3px; min-width: 20px; height: 20px; padding: 0 4px;
        background: var(--dj-gold); color: var(--dj-maroon-dark); font-weight: 700; font-size: 11px;
        border-radius: 50%; display: flex; align-items: center; justify-content: center;
        box-shadow: 0 0 0 2px var(--dj-maroon), 0 1px 3px rgba(0,0,0,.3);
    }
    #dj-cart-float-count[hidden] { display: none; }
    body.dj-en #dj-cart-float-count { right: auto; left: -3px; }
</style>
<button type="button" id="dj-cart-float" class="dj-keep-clickable" onclick="djOpenCart()"
        aria-label="{{ __('Shopping Cart') }}" title="{{ __('Shopping Cart') }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
    <span id="dj-cart-float-count"{{ ($cartCount ?? 0) < 1 ? ' hidden' : '' }}>{{ min($cartCount ?? 0, 99) }}{{ ($cartCount ?? 0) > 99 ? '+' : '' }}</span>
</button>
