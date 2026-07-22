{{-- Site-wide floating shortcut to order tracking — stacks between the
     WhatsApp float (closest to the corner) and #dj-back-to-top (pushed up
     to make room, see app.css). Auth state decides the destination
     server-side, not a client-side guess: a logged-in customer's own order
     history already has richer tracking (statuses, invoices) than the
     guest lookup form would give them.

     Inline (not resources/css/app.css) so this ships the moment this file
     reaches production via a plain git pull — same standing deploy-proofing
     convention as the WhatsApp float right next to it. --}}
<style>
    #dj-tracking-float {
        position: fixed; bottom: 92px; right: 26px; width: 56px; height: 56px; border-radius: 50%;
        background: var(--dj-maroon); color: var(--dj-gold); display: flex; align-items: center; justify-content: center;
        box-shadow: var(--dj-shadow); z-index: 85; transition: background .2s, transform .15s;
    }
    #dj-tracking-float:hover { background: var(--dj-maroon-dark); transform: scale(1.06); }
    #dj-tracking-float svg { width: 26px; height: 26px; }
    body.dj-en #dj-tracking-float { right: auto; left: 26px; }
</style>
<a id="dj-tracking-float"
   href="{{ auth()->check() ? route('account.orders.index') : route('track-order.form') }}"
   aria-label="{{ __('orders.track_title') }}" title="{{ __('orders.track_title') }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.85H14.25M16.5 18.75h-2.25m0-11.177v11.177m0-11.177L12.83 5.055a2.056 2.056 0 0 0-1.66-.805H4.5a2.25 2.25 0 0 0-2.25 2.25v9.75c0 .621.504 1.125 1.125 1.125H4.5"/></svg>
</a>
