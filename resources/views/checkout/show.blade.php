@extends('layouts.storefront')

@section('title', __('Checkout') . ' — Dar El-Jamila')

@section('content')
    <section class="dj-page-hero dj-photo-wrap dj-tint-maroon dj-strong" style="min-height:220px;">
        <img src="{{ setting_image_url($heroImage) }}" alt="">
        <div class="dj-lattice-bg"></div>
        <div class="dj-eyebrow">{{ __('Checkout') }}</div>
        <h1>{{ __('Complete Your Order') }}</h1>
    </section>

    <div class="dj-checkout-wrap">
        <div class="dj-checkout-form-box">
            @error('stock')
                <p class="dj-checkout-stock-error">{{ $message }}</p>
            @enderror
            @if ($hasStockIssues)
                <p class="dj-checkout-stock-error">{{ __('One or more items in your cart exceed available stock. Please update your cart before placing the order.') }}</p>
            @endif
            <h3>{{ __('Shipping Details') }}</h3>
            <form method="POST" action="{{ route('checkout.store') }}" id="dj-checkout-form">
                @csrf

                <div class="dj-form-row">
                    <div>
                        <input type="text" name="customer_name" value="{{ old('customer_name', auth()->user()->name ?? '') }}" placeholder="{{ __('Full Name') }}" aria-label="{{ __('Full Name') }}" required>
                        @error('customer_name') <p style="color:var(--dj-rose-dust); font-size:12px; margin-top:-10px; margin-bottom:10px;">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <input type="text" name="customer_phone" value="{{ old('customer_phone', auth()->user()->phone ?? '') }}" placeholder="{{ __('Phone Number') }}" aria-label="{{ __('Phone Number') }}" required>
                        @error('customer_phone') <p style="color:var(--dj-rose-dust); font-size:12px; margin-top:-10px; margin-bottom:10px;">{{ $message }}</p> @enderror
                    </div>
                </div>

                <input type="email" name="customer_email" value="{{ old('customer_email', auth()->user()->email ?? '') }}" placeholder="{{ __('Email') }}" aria-label="{{ __('Email') }}" required>
                @error('customer_email') <p style="color:var(--dj-rose-dust); font-size:12px; margin-top:-10px; margin-bottom:10px;">{{ $message }}</p> @enderror

                <div class="dj-form-row">
                    <div>
                        <input type="text" name="governorate" value="{{ old('governorate') }}" placeholder="{{ __('Governorate') }}" aria-label="{{ __('Governorate') }}" required>
                        @error('governorate') <p style="color:var(--dj-rose-dust); font-size:12px; margin-top:-10px; margin-bottom:10px;">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <input type="text" name="city" value="{{ old('city') }}" placeholder="{{ __('City / District') }}" aria-label="{{ __('City / District') }}" required>
                        @error('city') <p style="color:var(--dj-rose-dust); font-size:12px; margin-top:-10px; margin-bottom:10px;">{{ $message }}</p> @enderror
                    </div>
                </div>

                <textarea name="address" rows="3" placeholder="{{ __('Full Address') }}" aria-label="{{ __('Full Address') }}" required>{{ old('address') }}</textarea>
                @error('address') <p style="color:var(--dj-rose-dust); font-size:12px; margin-top:-10px; margin-bottom:10px;">{{ $message }}</p> @enderror

                <textarea name="notes" rows="2" placeholder="{{ __('Order notes (optional)') }}" aria-label="{{ __('Order notes (optional)') }}">{{ old('notes') }}</textarea>

                <h3 style="margin-top:8px;">{{ __('Shipping Method') }}</h3>
                <div class="dj-pay-methods">
                    @foreach ($shippingMethods as $method)
                        <label class="dj-pay-option {{ $loop->first ? 'dj-active' : '' }}" onclick="djCheckoutSelectShipping(this)">
                            <input type="radio" name="shipping_method_id" value="{{ $method->id }}" {{ $loop->first ? 'checked' : '' }} required style="display:none;">
                            <div class="dj-radio"></div>
                            <div style="flex:1;">
                                <strong>{{ trans_field($method, 'name') }}</strong>
                                <span class="dj-sub">{{ $method->estimated_days }} {{ __('days') }}</span>
                            </div>
                            <span class="dj-pay-fee" style="font-weight:700; color:var(--dj-maroon);">{{ number_format($method->fee) }} EGP</span>
                        </label>
                    @endforeach
                </div>
                @error('shipping_method_id') <p style="color:var(--dj-rose-dust); font-size:12px; margin-top:-10px; margin-bottom:10px;">{{ $message }}</p> @enderror

                <h3>{{ __('Payment Method') }}</h3>
                <div class="dj-pay-methods">
                    <div class="dj-pay-option dj-active">
                        <div class="dj-radio"></div>
                        <div>
                            <strong>{{ __('Cash on Delivery') }}</strong>
                            <span class="dj-sub">{{ __('Pay in cash when your order arrives') }}</span>
                        </div>
                    </div>
                </div>

                <button type="submit" class="dj-place-order-btn" id="dj-checkout-submit" {{ $hasStockIssues ? 'disabled' : '' }}>{{ __('Place Order') }}</button>
            </form>
        </div>

        <div class="dj-order-summary">
            <h3>{{ __('Order Summary') }}</h3>
            @foreach ($items as $item)
                <div class="dj-os-item">
                    <div class="dj-os-thumb dj-photo-wrap dj-tint-maroon">
                        @if ($item['product']->cover_image_src)
                            <img src="{{ $item['product']->cover_image_src }}" alt="">
                        @endif
                    </div>
                    <div class="dj-os-info">
                        <h4>{{ trans_field($item['product'], 'name') }}</h4>
                        <span>{{ __('Size') }} {{ $item['size'] }} × {{ $item['quantity'] }}</span>
                        @if ($item['exceeds_stock'])
                            <p class="dj-ci-warning">{{ $item['stock'] > 0 ? __('Only :count left', ['count' => $item['stock']]) : __('Sold out') }}</p>
                        @endif
                    </div>
                    <div class="dj-os-price">{{ number_format($item['subtotal']) }} EGP</div>
                </div>
            @endforeach

            <div class="dj-os-row"><span>{{ __('Subtotal') }}</span><span>{{ number_format($subtotal) }} EGP</span></div>
            @if ($discount > 0)
                <div class="dj-os-row" style="color:#2f7a4d;"><span>{{ __('Discount') }}</span><span>-{{ number_format($discount) }} EGP</span></div>
            @endif
            <div class="dj-os-row" id="dj-os-shipping"><span>{{ __('Shipping') }}</span><span>{{ number_format($shippingMethods->first()?->fee ?? 0) }} EGP</span></div>
            <div class="dj-os-row dj-total" id="dj-os-total"><span>{{ __('Total') }}</span><span>{{ number_format($subtotal - $discount + ($shippingMethods->first()?->fee ?? 0)) }} EGP</span></div>
        </div>
    </div>

    <script>
        const djCheckoutSubtotal = {{ $subtotal - $discount }};
        function djCheckoutSelectShipping(label) {
            document.querySelectorAll('#dj-checkout-form .dj-pay-option').forEach(o => {
                if (o.querySelector('input[type=radio]')) o.classList.remove('dj-active');
            });
            label.classList.add('dj-active');
            label.querySelector('input[type=radio]').checked = true;

            const feeText = label.querySelector('.dj-pay-fee').textContent;
            const fee = parseInt(feeText.replace(/[^0-9]/g, ''), 10) || 0;
            document.querySelector('#dj-os-shipping span:last-child').textContent = fee.toLocaleString() + ' EGP';
            document.querySelector('#dj-os-total span:last-child').textContent = (djCheckoutSubtotal + fee).toLocaleString() + ' EGP';
        }
        document.getElementById('dj-checkout-form').addEventListener('submit', function () {
            const btn = document.getElementById('dj-checkout-submit');
            btn.disabled = true;
            btn.textContent = '{{ __('Placing your order...') }}';
        });
    </script>
@endsection
