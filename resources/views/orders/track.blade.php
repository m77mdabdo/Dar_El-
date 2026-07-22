@extends('layouts.storefront')

@section('title', $order->order_number . ' — ' . __('orders.track_title') . ' — Dar El Jamila')

@section('content')
    @php
        $djStageIcons = [
            'pending' => 'M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
            'processing' => 'M20.25 7.5l-8.25 4.5L3.75 7.5M3.75 7.5l8.25-4.5 8.25 4.5M3.75 7.5v9l8.25 4.5m0-9v9m0-9l8.25-4.5m0 4.5v9l-8.25 4.5',
            'shipped' => 'M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.85H14.25M16.5 18.75h-2.25m0-11.177v11.177m0-11.177L12.83 5.055a2.056 2.056 0 0 0-1.66-.805H4.5a2.25 2.25 0 0 0-2.25 2.25v9.75c0 .621.504 1.125 1.125 1.125H4.5',
            'delivered' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        ];
        $djCheckIcon = 'M4.5 12.75l6 6 9-13.5';
        $djSteps = $order->trackingSteps();
        $djProgress = $order->trackingProgressPercent();
        $djCancelledAt = $order->statusHistories->firstWhere('status', 'cancelled')?->created_at;
        $djCancelledNote = $order->statusHistories->firstWhere('status', 'cancelled')?->note;
    @endphp

    <div style="max-width:900px; margin:0 auto; padding:40px 6% 90px;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; margin-bottom:8px;">
            @if ($isGuest)
                <a href="{{ route('track-order.form') }}" style="font-size:13px; color:var(--dj-maroon); text-decoration:underline;">{{ app()->getLocale() === 'ar' ? '→' : '←' }} {{ __('orders.track_another') }}</a>
            @else
                <a href="{{ route('account.orders.index') }}" style="font-size:13px; color:var(--dj-maroon); text-decoration:underline;">{{ app()->getLocale() === 'ar' ? '→' : '←' }} {{ __('My Orders') }}</a>
            @endif
        </div>

        <h1 style="font-size:26px; color:var(--dj-maroon); margin-bottom:6px;">{{ $order->order_number }}</h1>
        <p style="font-size:13.5px; color:#8a6b70; margin-bottom:32px;">{{ $order->created_at->translatedFormat('F j, Y') }}</p>

        {{-- ===== Tracker ===== --}}
        <div style="background:#fff; border-radius:20px; box-shadow:0 12px 32px -18px rgba(60,11,23,.25); padding:clamp(24px,5vw,44px) clamp(18px,4vw,36px); margin-bottom:32px;">
            @if ($order->isCancelled())
                <div style="text-align:center; padding:20px 10px;">
                    <div style="width:64px; height:64px; border-radius:50%; background:rgba(156,80,100,.12); color:var(--dj-rose-dust); display:flex; align-items:center; justify-content:center; margin:0 auto 16px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="width:30px; height:30px;"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    </div>
                    <h2 style="font-size:19px; color:var(--dj-rose-dust); margin-bottom:6px;">{{ __('orders.status_cancelled') }}</h2>
                    @if ($djCancelledAt)
                        <p style="font-size:13px; color:#8a6b70;">{{ $djCancelledAt->translatedFormat('F j, Y — g:i A') }}</p>
                    @endif
                    @if ($djCancelledNote)
                        <p style="font-size:13px; color:#8a6b70; margin-top:8px; max-width:420px; margin-inline:auto;">{{ $djCancelledNote }}</p>
                    @endif
                </div>
            @else
                <div class="dj-tracker-steps" style="--dj-progress:{{ $djProgress }}%;">
                    <div class="dj-tracker-line"><span class="dj-tracker-line-fill"></span></div>
                    @foreach ($djSteps as $step)
                        <div class="dj-tracker-step {{ $step['completed'] ? 'dj-t-completed' : '' }} {{ $step['current'] ? 'dj-t-current' : '' }}">
                            <span class="dj-tracker-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $step['completed'] && ! $step['current'] ? $djCheckIcon : $djStageIcons[$step['key']] }}"/></svg>
                            </span>
                            <div class="dj-tracker-text">
                                <span class="dj-tracker-label">{{ __('orders.status_'.$step['key']) }}</span>
                                <span class="dj-tracker-time">{{ $step['timestamp']?->translatedFormat('M j — g:i A') ?? __('orders.track_awaiting') }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ===== Order summary ===== --}}
        <div style="display:grid; grid-template-columns:1fr; gap:20px;" class="dj-tracker-summary-grid">
            <div style="background:#fff; border-radius:16px; box-shadow:0 10px 24px -18px rgba(60,11,23,.2); overflow:hidden;">
                @foreach ($order->items as $item)
                    <div style="padding:16px; display:flex; align-items:center; gap:14px; font-size:13.5px; {{ ! $loop->last ? 'border-bottom:1px solid var(--dj-cream-2);' : '' }}">
                        @if ($item->product?->cover_image_src)
                            <img src="{{ $item->product->cover_image_src }}" alt="" style="width:56px; height:56px; border-radius:12px; object-fit:cover; border:1px solid var(--dj-cream-2); flex-shrink:0;">
                        @endif
                        <div style="flex:1; min-width:0;">
                            <p style="font-weight:700; color:var(--dj-ink); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $item->product ? trans_field($item->product, 'name') : $item->product_name }}</p>
                            <p style="color:#8a6b70; margin-top:2px;">
                                @if ($item->size){{ __('orders.item_size', ['size' => $item->size]) }} &middot; @endif
                                {{ __('orders.item_qty', ['qty' => $item->quantity]) }}
                            </p>
                        </div>
                        <span style="font-weight:700; color:var(--dj-maroon); flex-shrink:0;">{{ number_format($item->line_total) }} EGP</span>
                    </div>
                @endforeach
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;" class="dj-tracker-info-grid">
                <div style="background:#fff; border-radius:16px; box-shadow:0 10px 24px -18px rgba(60,11,23,.2); padding:18px; font-size:13.5px;">
                    <h2 style="font-weight:700; color:var(--dj-maroon); margin-bottom:8px; font-size:14px;">{{ __('orders.shipping_address') }}</h2>
                    <p style="color:var(--dj-ink);">{{ $order->address }}</p>
                    <p style="color:var(--dj-ink);">{{ $order->city }}, {{ $order->governorate }}</p>
                    <p style="color:#8a6b70; margin-top:6px;">{{ $order->customer_phone }}</p>
                </div>
                <div style="background:#fff; border-radius:16px; box-shadow:0 10px 24px -18px rgba(60,11,23,.2); padding:18px; font-size:13.5px;">
                    <h2 style="font-weight:700; color:var(--dj-maroon); margin-bottom:8px; font-size:14px;">{{ __('Total') }}</h2>
                    <div style="display:flex; justify-content:space-between; padding:3px 0; color:#8a6b70;"><span>{{ __('orders.subtotal') }}</span><span>{{ number_format($order->subtotal) }} EGP</span></div>
                    <div style="display:flex; justify-content:space-between; padding:3px 0; color:#8a6b70;"><span>{{ __('orders.shipping') }}</span><span>{{ number_format($order->shipping_fee) }} EGP</span></div>
                    @if ($order->discount_amount > 0)
                        <div style="display:flex; justify-content:space-between; padding:3px 0; color:#2f7a4d;"><span>{{ __('orders.discount') }}</span><span>-{{ number_format($order->discount_amount) }} EGP</span></div>
                    @endif
                    <div style="display:flex; justify-content:space-between; padding:8px 0 0; margin-top:4px; border-top:1px solid var(--dj-cream-2); font-weight:700; color:var(--dj-maroon);"><span>{{ __('Total') }}</span><span>{{ number_format($order->total) }} EGP</span></div>
                </div>
            </div>
        </div>

        <div style="margin-top:20px;">
            @include('partials.order-change-request', [
                'order' => $order,
                'changeRequestActionUrl' => $changeRequestActionUrl ?? null,
                'existingChangeRequest' => $existingChangeRequest ?? null,
            ])
        </div>
    </div>

    {{-- Inline CSS (not resources/css/app.css) per this project's standing
         deploy-proofing rule — a customer-facing feature must render
         correctly the moment this Blade file reaches production via a
         plain git pull, with no separate npm run build + rezip step. --}}
    <style>
        .dj-tracker-steps { display:flex; align-items:flex-start; justify-content:space-between; position:relative; gap:8px; }
        .dj-tracker-line { position:absolute; top:27px; left:0; right:0; height:3px; background:var(--dj-cream-2); z-index:1; border-radius:3px; overflow:hidden; }
        .dj-tracker-line-fill { display:block; height:100%; width:var(--dj-progress); background:var(--dj-maroon); transition:width .5s ease; float:right; }
        body.dj-en .dj-tracker-line-fill { float:left; }

        .dj-tracker-step { position:relative; z-index:2; display:flex; flex-direction:column; align-items:center; text-align:center; flex:1; min-width:0; }
        .dj-tracker-icon {
            width:56px; height:56px; border-radius:50%; display:flex; align-items:center; justify-content:center;
            background:var(--dj-cream); color:#b9a3a7; border:2px solid var(--dj-cream-2); margin-bottom:10px; transition:.3s;
        }
        .dj-tracker-icon svg { width:24px; height:24px; }
        .dj-tracker-step.dj-t-completed .dj-tracker-icon { background:var(--dj-maroon); color:var(--dj-gold); border-color:var(--dj-maroon); }
        .dj-tracker-step.dj-t-current .dj-tracker-icon { background:var(--dj-gold); color:var(--dj-maroon-dark); border-color:var(--dj-gold); box-shadow:0 0 0 6px rgba(232,195,154,.35); }
        .dj-tracker-text { display:flex; flex-direction:column; align-items:center; }
        .dj-tracker-label { font-size:12.5px; font-weight:700; color:#b9a3a7; }
        .dj-tracker-step.dj-t-completed .dj-tracker-label, .dj-tracker-step.dj-t-current .dj-tracker-label { color:var(--dj-maroon); }
        .dj-tracker-time { font-size:10.5px; color:#a68b90; margin-top:3px; }

        @media (max-width: 700px) {
            .dj-tracker-steps { flex-direction:column; align-items:stretch; gap:22px; }
            .dj-tracker-line { top:0; bottom:0; left:27px; right:auto; width:3px; height:auto; }
            body.dj-en .dj-tracker-line { left:auto; right:27px; }
            .dj-tracker-line-fill { width:100%; height:var(--dj-progress); float:none; }
            .dj-tracker-step { flex-direction:row; text-align:start; gap:14px; }
            .dj-tracker-icon { margin-bottom:0; flex-shrink:0; }
            .dj-tracker-text { align-items:flex-start; }

            .dj-tracker-info-grid { grid-template-columns:1fr !important; }
        }
    </style>
@endsection
