{{-- Reusable time-limited-offer countdown banner. $endsAt (Carbon|null) and
     $label (string|null) are resolved by the including view (site-wide
     Settings on home/shop-index, per-product-vs-site-wide precedence on the
     product page) — this partial only renders when $endsAt is set and still
     in the future, and re-checks that defensively even though callers
     already should have.

     Appears at most once per page, so a fixed #dj-offer-countdown id is
     fine — no need to generate a unique one per include.

     Inline CSS/JS only (not resources/css/app.css or resources/js/app.js)
     per this project's standing deploy-proofing rule: compiled bundle
     changes require a manual rebuild+re-zip of public/build.zip, so
     customer-facing widgets like this one ship correctly on a plain git
     pull with no extra step. --}}
@if (($endsAt ?? null) && $endsAt->isFuture())
    <div class="dj-offer-countdown" id="dj-offer-countdown" data-ends-at="{{ $endsAt->toIso8601String() }}">
        <span class="dj-offer-countdown-label">{{ $label }}</span>
        <div class="dj-offer-countdown-timer">
            <div class="dj-offer-countdown-unit"><span data-unit="days">00</span><small>{{ __('Days') }}</small></div>
            <div class="dj-offer-countdown-unit"><span data-unit="hours">00</span><small>{{ __('Hours') }}</small></div>
            <div class="dj-offer-countdown-unit"><span data-unit="minutes">00</span><small>{{ __('Min') }}</small></div>
            <div class="dj-offer-countdown-unit"><span data-unit="seconds">00</span><small>{{ __('Sec') }}</small></div>
        </div>
    </div>

    <style>
        .dj-offer-countdown {
            display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: 16px;
            max-width: 1100px; margin: 0 auto 28px; padding: 16px 24px; border-radius: 16px; text-align: center;
            background: linear-gradient(135deg, var(--dj-maroon-dark), var(--dj-maroon)); box-shadow: var(--dj-shadow);
        }
        .dj-offer-countdown-label { font-size: 14px; font-weight: 700; letter-spacing: .3px; color: var(--dj-gold); }
        .dj-offer-countdown-timer { display: flex; gap: 8px; }
        .dj-offer-countdown-unit {
            display: flex; flex-direction: column; align-items: center; min-width: 52px;
            background: rgba(255,255,255,.1); border-radius: 10px; padding: 7px 10px;
        }
        .dj-offer-countdown-unit span { font-size: 20px; font-weight: 800; line-height: 1; color: var(--dj-cream); font-variant-numeric: tabular-nums; }
        .dj-offer-countdown-unit small { margin-top: 4px; font-size: 9.5px; text-transform: uppercase; letter-spacing: .4px; color: var(--dj-cream-2); }
        @media (max-width: 600px) {
            .dj-offer-countdown { padding: 12px 16px; gap: 10px; }
            .dj-offer-countdown-unit { min-width: 44px; padding: 6px 8px; }
            .dj-offer-countdown-unit span { font-size: 16px; }
        }
    </style>
    <script>
        (function () {
            if (typeof window.djInitOfferCountdown !== 'function') {
                window.djInitOfferCountdown = function (el) {
                    // el.dataset.endsAt is an ISO 8601 string with an explicit
                    // UTC offset (Carbon::toIso8601String()) — the Date
                    // constructor parses that as an absolute instant, and every
                    // arithmetic op below runs against Date.now(), which is
                    // always the visitor's own local clock. No manual timezone
                    // math needed for this to be correct in any timezone.
                    var endsAt = new Date(el.dataset.endsAt).getTime();
                    var pad = function (n) { return String(n).padStart(2, '0'); };
                    var dayEl = el.querySelector('[data-unit="days"]');
                    var hourEl = el.querySelector('[data-unit="hours"]');
                    var minEl = el.querySelector('[data-unit="minutes"]');
                    var secEl = el.querySelector('[data-unit="seconds"]');
                    var intervalId = null;

                    function tick() {
                        var diff = endsAt - Date.now();

                        if (diff <= 0) {
                            el.style.display = 'none';
                            if (intervalId) clearInterval(intervalId);
                            return;
                        }

                        dayEl.textContent = pad(Math.floor(diff / 86400000));
                        hourEl.textContent = pad(Math.floor((diff % 86400000) / 3600000));
                        minEl.textContent = pad(Math.floor((diff % 3600000) / 60000));
                        secEl.textContent = pad(Math.floor((diff % 60000) / 1000));
                    }

                    tick();
                    intervalId = setInterval(tick, 1000);
                };
            }

            window.djInitOfferCountdown(document.getElementById('dj-offer-countdown'));
        })();
    </script>
@endif
