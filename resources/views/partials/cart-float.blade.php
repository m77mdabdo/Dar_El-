{{-- Site-wide floating shortcut to the cart drawer — stacks between the
     WhatsApp float (closest to the corner) and #dj-back-to-top (pushed up
     to make room, see app.css). A second entry point to the exact same
     drawer/behavior as the cart button in the navbar — djOpenCart() is a
     plain global function with no dependency on which element called it,
     so this is genuinely the same action, not a re-implementation.

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
    #dj-cart-float svg { width: 26px; height: 26px; }
    body.dj-en #dj-cart-float { right: auto; left: 26px; }
</style>
<button type="button" id="dj-cart-float" class="dj-keep-clickable" onclick="djOpenCart()"
        aria-label="{{ __('Shopping Cart') }}" title="{{ __('Shopping Cart') }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
</button>
