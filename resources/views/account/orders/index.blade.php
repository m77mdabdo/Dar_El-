@extends('layouts.storefront')

@section('title', __('My Orders') . ' — Dar El Jamila')

@section('content')
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-8">
            <h1 class="font-serif text-3xl">{{ __('My Orders') }}</h1>

            {{-- Inline (not resources/css/app.css or resources/js/app.js) so
                 this ships the moment this file reaches production via a
                 plain git pull — same standing convention as the WhatsApp
                 button and size guide elsewhere. Hidden entirely when push
                 isn't supported/configured (see the script below), rather
                 than showing a button that would just silently fail. --}}
            <button type="button" id="dj-orders-push-optin" class="dj-order-btn dj-order-btn-secondary" style="display:none;" onclick="djOrdersPagePushOptin()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
                <span id="dj-orders-push-optin-label">{{ __('Get notified when your order ships or arrives') }}</span>
            </button>
        </div>

        <script>
            (function () {
                var btn = document.getElementById('dj-orders-push-optin');
                var label = document.getElementById('dj-orders-push-optin-label');
                if (!btn) return;

                var supported = 'serviceWorker' in navigator && 'PushManager' in window
                    && typeof Notification !== 'undefined'
                    && document.querySelector('meta[name="webpush-public-key"]');
                if (!supported || Notification.permission === 'denied') return;

                if (Notification.permission === 'granted') {
                    label.textContent = @json(__('Notifications enabled'));
                    btn.disabled = true;
                    btn.style.opacity = '.7';
                }
                btn.style.display = '';

                window.djOrdersPagePushOptin = async function () {
                    btn.disabled = true;
                    var ok = await window.djSubscribeToPush();
                    if (ok) {
                        label.textContent = @json(__('Notifications enabled'));
                        btn.style.opacity = '.7';
                    } else {
                        btn.disabled = false;
                    }
                };
            })();
        </script>

        @php
            // bg/color per real admin-managed status (Order::TRACKING_STAGES
            // + cancelled) — same vocabulary the tracking page uses, just a
            // distinct color per stage here instead of a stepper.
            $djStatusColors = [
                'pending' => ['bg' => 'rgba(232,195,154,.35)', 'fg' => '#8a5a2a'],
                'processing' => ['bg' => 'rgba(59,130,246,.12)', 'fg' => '#2563eb'],
                'shipped' => ['bg' => 'rgba(147,51,234,.12)', 'fg' => '#7e22ce'],
                'delivered' => ['bg' => 'rgba(47,122,77,.12)', 'fg' => '#2f7a4d'],
                'cancelled' => ['bg' => 'rgba(156,80,100,.12)', 'fg' => '#9C5064'],
            ];
        @endphp

        @if ($orders->isEmpty())
            <p class="p-6 bg-white border border-stone-200 rounded-lg text-stone-500 text-sm">{{ __("You haven't placed any orders yet.") }}</p>
        @else
            <div class="space-y-4">
                @foreach ($orders as $order)
                    @php $djColor = $djStatusColors[$order->status] ?? $djStatusColors['pending']; @endphp
                    <div class="dj-order-card">
                        <div class="dj-order-card-main">
                            <div class="dj-order-thumbs">
                                @forelse ($order->items->take(3) as $i => $item)
                                    <div class="dj-order-thumb" style="z-index:{{ 3 - $i }};">
                                        @if ($item->product?->cover_image_src)
                                            <img src="{{ $item->product->cover_image_src }}" alt="">
                                        @else
                                            <span class="dj-order-thumb-fallback">🛍️</span>
                                        @endif
                                    </div>
                                @empty
                                    <div class="dj-order-thumb"><span class="dj-order-thumb-fallback">🛍️</span></div>
                                @endforelse
                                @if ($order->items->count() > 3)
                                    <div class="dj-order-thumb dj-order-thumb-more" style="z-index:0;">+{{ $order->items->count() - 3 }}</div>
                                @endif
                            </div>

                            <div class="dj-order-info">
                                <a href="{{ route('account.orders.show', $order) }}" class="dj-order-card-number">{{ $order->order_number }}</a>
                                <p class="dj-order-date">{{ $order->created_at->translatedFormat('F j, Y') }}</p>
                                <span class="dj-order-badge" style="background:{{ $djColor['bg'] }}; color:{{ $djColor['fg'] }};">{{ __('orders.status_'.$order->status) }}</span>
                            </div>
                        </div>

                        <div class="dj-order-card-actions">
                            <p class="dj-order-total">{{ number_format($order->total) }} EGP</p>
                            <div class="dj-order-cta-row">
                                <a href="{{ route('account.orders.track', $order) }}" class="dj-order-btn dj-order-btn-primary">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.85H14.25M16.5 18.75h-2.25m0-11.177v11.177m0-11.177L12.83 5.055a2.056 2.056 0 0 0-1.66-.805H4.5a2.25 2.25 0 0 0-2.25 2.25v9.75c0 .621.504 1.125 1.125 1.125H4.5"/></svg>
                                    {{ __('orders.track_title') }}
                                </a>
                                <a href="{{ route('account.orders.invoice', $order) }}" class="dj-order-btn dj-order-btn-secondary">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                    {{ __('Invoice') }}
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="mt-6">
            {{ $orders->links() }}
        </div>
    </div>

    {{-- Inline (not resources/css/app.css) so this order-card redesign
         renders correctly the moment this Blade file reaches production via
         a plain git pull — same standing deploy-proofing convention as the
         rest of this project's newer storefront features. --}}
    <style>
        .dj-order-card {
            background: #fff; border-radius: 18px; padding: 18px 22px; box-shadow: 0 12px 28px -20px rgba(60,11,23,.3);
            display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px; transition: .25s;
        }
        .dj-order-card:hover { box-shadow: 0 18px 36px -18px rgba(60,11,23,.4); transform: translateY(-2px); }

        .dj-order-card-main { display: flex; align-items: center; gap: 16px; }
        .dj-order-thumbs { display: flex; flex-shrink: 0; }
        .dj-order-thumb {
            width: 52px; height: 52px; border-radius: 12px; overflow: hidden; background: var(--dj-cream-2);
            border: 2px solid #fff; box-shadow: 0 2px 6px rgba(60,11,23,.15); margin-inline-start: -16px;
            display: flex; align-items: center; justify-content: center; position: relative;
        }
        .dj-order-thumb:first-child { margin-inline-start: 0; }
        .dj-order-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .dj-order-thumb-fallback { font-size: 18px; opacity: .6; }
        .dj-order-thumb-more { background: var(--dj-maroon); color: var(--dj-gold); font-size: 12px; font-weight: 700; }

        .dj-order-info { display: flex; flex-direction: column; gap: 4px; align-items: flex-start; }
        .dj-order-card-number { font-weight: 700; font-size: 15px; color: var(--dj-maroon); }
        .dj-order-card-number:hover { text-decoration: underline; }
        .dj-order-date { font-size: 12.5px; color: #8a6b70; }
        .dj-order-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11.5px; font-weight: 700; }

        .dj-order-card-actions { display: flex; flex-direction: column; align-items: flex-end; gap: 10px; margin-inline-start: auto; }
        .dj-order-total { font-weight: 700; font-size: 15px; color: var(--dj-ink); }
        .dj-order-cta-row { display: flex; align-items: center; gap: 8px; }
        .dj-order-btn { display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; font-weight: 700; padding: 8px 14px; border-radius: 10px; transition: .2s; white-space: nowrap; }
        .dj-order-btn svg { width: 15px; height: 15px; flex-shrink: 0; }
        .dj-order-btn-primary { background: var(--dj-maroon); color: var(--dj-gold); }
        .dj-order-btn-primary:hover { background: var(--dj-maroon-dark); }
        .dj-order-btn-secondary { background: transparent; color: var(--dj-maroon); border: 1.5px solid var(--dj-cream-2); }
        .dj-order-btn-secondary:hover { background: var(--dj-cream-2); }

        @media (max-width: 640px) {
            .dj-order-card { flex-direction: column; align-items: stretch; }
            .dj-order-card-actions { align-items: stretch; margin-inline-start: 0; }
            .dj-order-total { text-align: end; }
            .dj-order-cta-row { justify-content: flex-end; }
        }
    </style>
@endsection
