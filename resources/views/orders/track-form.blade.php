@extends('layouts.storefront')

@section('title', __('orders.track_title') . ' — Dar El Jamila')
@section('meta_description', __('orders.track_meta_description'))

@section('content')
    <section class="dj-page-hero dj-tint-maroon dj-strong" style="background: linear-gradient(135deg, var(--dj-maroon-dark), var(--dj-maroon)); position:relative; padding:clamp(70px,14vw,110px) 6% clamp(50px,8vw,80px); text-align:center; overflow:hidden;">
        <div class="dj-lattice-bg"></div>
        <div class="dj-eyebrow">{{ __('orders.track_eyebrow') }}</div>
        <h1>{{ __('orders.track_title') }}</h1>
        <p>{{ __('orders.track_intro') }}</p>
    </section>

    <div style="max-width:480px; margin:0 auto; padding:50px 6% 90px;">
        @if (session('error'))
            <p style="background:rgba(156,80,100,.1); border:1px solid rgba(156,80,100,.3); color:var(--dj-rose-dust); font-size:13.5px; font-weight:600; padding:12px 16px; border-radius:12px; margin-bottom:20px; text-align:center;">
                {{ session('error') }}
            </p>
        @endif

        <form method="POST" action="{{ route('track-order.lookup') }}" style="display:flex; flex-direction:column; gap:16px;">
            @csrf
            <div>
                <label style="display:block; font-size:12px; font-weight:700; color:var(--dj-maroon); margin-bottom:6px;">{{ __('orders.track_order_number') }}</label>
                <input type="text" name="order_number" value="{{ old('order_number') }}" placeholder="ORD-2026-000123" required
                       style="width:100%; padding:13px 16px; border:1.5px solid var(--dj-cream-2); border-radius:12px; font-family:inherit; font-size:14px; background:var(--dj-cream); color:var(--dj-ink);">
                @error('order_number') <p style="color:var(--dj-rose-dust); font-size:12px; margin-top:4px;">{{ $message }}</p> @enderror
            </div>
            <div>
                <label style="display:block; font-size:12px; font-weight:700; color:var(--dj-maroon); margin-bottom:6px;">{{ __('orders.track_contact') }}</label>
                <input type="text" name="contact" value="{{ old('contact') }}" placeholder="{{ __('orders.track_contact_placeholder') }}" required
                       style="width:100%; padding:13px 16px; border:1.5px solid var(--dj-cream-2); border-radius:12px; font-family:inherit; font-size:14px; background:var(--dj-cream); color:var(--dj-ink);">
                @error('contact') <p style="color:var(--dj-rose-dust); font-size:12px; margin-top:4px;">{{ $message }}</p> @enderror
                <p style="font-size:11.5px; color:#8a6b70; margin-top:6px;">{{ __('orders.track_contact_hint') }}</p>
            </div>
            <button type="submit" class="dj-hero-cta" style="position:relative; justify-content:center; margin-top:6px;">{{ __('orders.track_submit') }}</button>
        </form>

        @auth
            <p style="text-align:center; font-size:13px; color:#8a6b70; margin-top:24px;">
                <a href="{{ route('account.orders.index') }}" style="color:var(--dj-maroon); font-weight:700; text-decoration:underline;">{{ __('My Orders') }}</a>
            </p>
        @endauth
    </div>
@endsection
