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

                <button type="button" id="dj-checkout-geolocate" style="display:flex; align-items:center; gap:6px; background:none; border:none; color:var(--dj-maroon); font-size:12.5px; font-weight:600; text-decoration:underline; padding:0; margin:-6px 0 12px;">
                    {{ __('Use My Current Location') }}
                </button>
                <p id="dj-checkout-geolocate-msg" style="font-size:11.5px; margin:-8px 0 12px; display:none;"></p>
                <input type="hidden" name="customer_latitude" id="dj-checkout-lat" value="{{ old('customer_latitude') }}">
                <input type="hidden" name="customer_longitude" id="dj-checkout-lng" value="{{ old('customer_longitude') }}">

                <textarea name="notes" rows="2" placeholder="{{ __('Order notes (optional)') }}" aria-label="{{ __('Order notes (optional)') }}">{{ old('notes') }}</textarea>

                <h3 style="margin-top:8px;">{{ __('Shipping Method') }}</h3>
                <div class="dj-pay-methods">
                    @foreach ($shippingMethods as $method)
                        <label class="dj-pay-option {{ $loop->first ? 'dj-active' : '' }}" onclick="djCheckoutSelectShipping(this)">
                            <input type="radio" name="shipping_method_id" value="{{ $method->id }}" {{ $loop->first ? 'checked' : '' }} required style="display:none;">
                            <div class="dj-radio"></div>
                            <div style="flex:1;">
                                <strong>{{ trans_field($method, 'name') }}</strong>
                                <span class="dj-sub">{{ $method->deliveryEstimateLabel() }}</span>
                            </div>
                            <span class="dj-pay-fee" style="font-weight:700; color:var(--dj-maroon);">{{ number_format($method->fee) }} EGP</span>
                        </label>
                    @endforeach
                </div>
                @error('shipping_method_id') <p style="color:var(--dj-rose-dust); font-size:12px; margin-top:-10px; margin-bottom:10px;">{{ $message }}</p> @enderror

                <h3>{{ __('Payment Method') }}</h3>
                <div class="dj-pay-methods">
                    <label class="dj-pay-option dj-active">
                        <input type="radio" name="payment_method" value="cash_on_delivery" checked required style="display:none;">
                        <div class="dj-radio"></div>
                        <div>
                            <strong>{{ __('Cash on Delivery') }}</strong>
                            <span class="dj-sub">{{ __('Pay in cash when your order arrives') }}</span>
                        </div>
                    </label>
                </div>
                @error('payment_method') <p style="color:var(--dj-rose-dust); font-size:12px; margin-top:10px;">{{ $message }}</p> @enderror

                @if ($errors->any())
                    <div id="dj-checkout-error-summary" class="dj-checkout-stock-error" style="margin-top:16px;">
                        <strong style="display:block; margin-bottom:6px;">{{ __('Please review the highlighted fields below:') }}</strong>
                        <ul style="margin:0; padding-inline-start:18px;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

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
        document.getElementById('dj-checkout-form').addEventListener('submit', function (e) {
            const form = e.target;
            if (!form.checkValidity()) {
                // Let the browser's own required-field validation block
                // submission first — the button must stay enabled so the
                // customer can fix the field and try again.
                return;
            }
            const btn = document.getElementById('dj-checkout-submit');
            btn.disabled = true;
            btn.textContent = '{{ __('Placing your order...') }}';
        });

        // Auto-scroll to the first validation error returned by the server
        // (old input and per-field error text are already rendered above;
        // this just brings them into view instead of leaving the customer
        // at the top of the page wondering why nothing happened).
        (function () {
            const firstError = document.querySelector('#dj-checkout-error-summary, #dj-checkout-form [style*="rose-dust"]');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        })();

        // "Use My Current Location" — optional convenience only. Manual
        // address fields above remain fully sufficient on their own; this
        // never blocks or disables the form if permission is denied or the
        // browser lacks geolocation support.
        document.getElementById('dj-checkout-geolocate').addEventListener('click', function () {
            const msg = document.getElementById('dj-checkout-geolocate-msg');
            const showMsg = (text, color) => {
                msg.textContent = text;
                msg.style.color = color;
                msg.style.display = 'block';
            };

            if (!navigator.geolocation) {
                showMsg('{{ __('Location services are not supported by your browser.') }}', 'var(--dj-rose-dust)');
                return;
            }

            showMsg('{{ __('Detecting your location...') }}', '#8a6b70');

            navigator.geolocation.getCurrentPosition(
                function (position) {
                    document.getElementById('dj-checkout-lat').value = position.coords.latitude;
                    document.getElementById('dj-checkout-lng').value = position.coords.longitude;
                    showMsg('{{ __('Location detected successfully.') }}', '#2f7a4d');
                },
                function () {
                    showMsg('{{ __('Could not access your location. Please enter your address manually.') }}', 'var(--dj-rose-dust)');
                }
            );
        });
    </script>
@endsection
