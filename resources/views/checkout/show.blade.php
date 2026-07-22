@extends('layouts.storefront')

@section('title', __('Checkout') . ' — Dar El Jamila')

@section('content')
    {{-- Own Vite entry (resources/js/checkout-map.js), not the universal
         app.js — Leaflet is ~40KB and only ever needed on this one page.
         See vite.config.js for the extra entry point, same convention
         already used for admin-products.js. --}}
    @vite(['resources/js/checkout-map.js'])

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
            <form method="POST" action="{{ route('checkout.store') }}" id="dj-checkout-form">
                @csrf

                <h3>{{ __('Contact Information') }}</h3>
                <div class="dj-form-row">
                    <div>
                        <input type="text" name="customer_name" autocomplete="name" class="{{ $errors->has('customer_name') ? 'dj-input-error' : '' }}" value="{{ old('customer_name', auth()->user()->name ?? '') }}" placeholder="{{ __('Full Name') }}" aria-label="{{ __('Full Name') }}" aria-invalid="{{ $errors->has('customer_name') ? 'true' : 'false' }}" required>
                        @error('customer_name') <p class="dj-field-msg-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <input type="text" name="customer_phone" autocomplete="tel" class="{{ $errors->has('customer_phone') ? 'dj-input-error' : '' }}" value="{{ old('customer_phone', auth()->user()->phone ?? '') }}" placeholder="{{ __('Phone Number') }}" aria-label="{{ __('Phone Number') }}" aria-invalid="{{ $errors->has('customer_phone') ? 'true' : 'false' }}" required>
                        @error('customer_phone') <p class="dj-field-msg-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <input type="email" name="customer_email" autocomplete="email" class="{{ $errors->has('customer_email') ? 'dj-input-error' : '' }}" value="{{ old('customer_email', auth()->user()->email ?? '') }}" placeholder="{{ __('Email (optional)') }}" aria-label="{{ __('Email (optional)') }}" aria-invalid="{{ $errors->has('customer_email') ? 'true' : 'false' }}">
                @error('customer_email') <p class="dj-field-msg-error">{{ $message }}</p> @enderror
                <p class="dj-checkout-email-hint">{{ __('Add your email to receive an order confirmation and invoice — you can always track your order by phone number instead.') }}</p>

                <div class="dj-checkout-section-divider"></div>

                <h3>{{ __('Delivery Address') }}</h3>

                {{-- Map picker: Leaflet + OpenStreetMap tiles, both free/open
                     source, no API key. Reverse-geocoded via Nominatim (OSM's
                     free geocoder) on every pin placement/drag — see
                     resources/js/checkout-map.js. The fields below are always
                     left editable afterward; a reverse-geocode result is a
                     starting point, never a lock. --}}
                <div id="dj-checkout-map" class="dj-checkout-map" aria-label="{{ __('Select your location on the map') }}"></div>
                <p class="dj-checkout-map-hint">{{ __('Click the map or drag the pin to your exact location — the fields below will fill in automatically, and you can still adjust them.') }}</p>

                <div class="dj-form-row">
                    <div>
                        <input type="text" name="governorate" id="dj-checkout-governorate" autocomplete="address-level1" class="{{ $errors->has('governorate') ? 'dj-input-error' : '' }}" value="{{ old('governorate') }}" placeholder="{{ __('Governorate') }}" aria-label="{{ __('Governorate') }}" aria-invalid="{{ $errors->has('governorate') ? 'true' : 'false' }}" required>
                        @error('governorate') <p class="dj-field-msg-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <input type="text" name="city" id="dj-checkout-city" autocomplete="address-level2" class="{{ $errors->has('city') ? 'dj-input-error' : '' }}" value="{{ old('city') }}" placeholder="{{ __('City / District') }}" aria-label="{{ __('City / District') }}" aria-invalid="{{ $errors->has('city') ? 'true' : 'false' }}" required>
                        @error('city') <p class="dj-field-msg-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <textarea name="address" id="dj-checkout-address" rows="3" autocomplete="street-address" class="{{ $errors->has('address') ? 'dj-input-error' : '' }}" placeholder="{{ __('Full Address') }}" aria-label="{{ __('Full Address') }}" aria-invalid="{{ $errors->has('address') ? 'true' : 'false' }}" required>{{ old('address') }}</textarea>
                @error('address') <p class="dj-field-msg-error">{{ $message }}</p> @enderror

                <button type="button" id="dj-checkout-geolocate" class="dj-checkout-geolocate-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21c-4.5-4.5-7.5-8.25-7.5-11.25a7.5 7.5 0 1115 0C19.5 12.75 16.5 16.5 12 21Z"/><circle cx="12" cy="9.75" r="2.5"/></svg>
                    {{ __('Use My Current Location') }}
                </button>
                <p id="dj-checkout-geolocate-msg" class="dj-checkout-geolocate-msg" style="display:none;"></p>
                <input type="hidden" name="customer_latitude" id="dj-checkout-lat" value="{{ old('customer_latitude') }}">
                <input type="hidden" name="customer_longitude" id="dj-checkout-lng" value="{{ old('customer_longitude') }}">

                <textarea name="notes" rows="2" placeholder="{{ __('Order notes (optional)') }}" aria-label="{{ __('Order notes (optional)') }}">{{ old('notes') }}</textarea>

                <div class="dj-checkout-section-divider"></div>

                <h3>{{ __('Shipping Method') }}</h3>
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

                <div class="dj-checkout-section-divider"></div>

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

                <button type="submit" class="dj-place-order-btn dj-keep-clickable" id="dj-checkout-submit" {{ $hasStockIssues ? 'disabled' : '' }}>{{ __('Place Order') }}</button>
            </form>

            <div class="dj-modal-trust" style="justify-content:center;">
                <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2 4 6v6c0 5 3.5 8.5 8 10 4.5-1.5 8-5 8-10V6l-8-4z"/></svg>{{ __('Secure Order') }}</span>
                <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a4 4 0 018 0v2"/></svg>{{ __('Nationwide Delivery') }}</span>
                <a href="{{ route('return-policy') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 2l4 4-4 4"/><path d="M3 11V9a4 4 0 014-4h14"/><path d="M7 22l-4-4 4-4"/><path d="M21 13v2a4 4 0 01-4 4H3"/></svg>{{ __('3-Day Exchange') }}</a>
            </div>
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

        // Shared status line for both the map (pin drop/drag) and the "Use
        // My Current Location" button below — same element either way, so
        // the customer only ever sees one status message at a time.
        const djCheckoutGeoMsg = document.getElementById('dj-checkout-geolocate-msg');
        function djShowCheckoutGeoMsg(text, color) {
            djCheckoutGeoMsg.textContent = text;
            djCheckoutGeoMsg.style.color = color;
            djCheckoutGeoMsg.style.display = 'block';
        }

        /**
         * Fills governorate/city/address from a Nominatim reverse-geocode
         * result. Deliberately just sets .value, never readonly/disabled —
         * the customer can freely correct anything the geocoder got wrong.
         */
        function djFillAddressFromNominatim(data) {
            const addr = data.address || {};
            const governorate = addr.state || addr.state_district || addr.region || '';
            const city = addr.city || addr.town || addr.village || addr.suburb || addr.county || '';
            const roadParts = [addr.road, addr.house_number, addr.neighbourhood && addr.neighbourhood !== city ? addr.neighbourhood : null].filter(Boolean);
            const addressLine = roadParts.length ? roadParts.join('، ') : (data.display_name || '');

            if (governorate) document.getElementById('dj-checkout-governorate').value = governorate;
            if (city) document.getElementById('dj-checkout-city').value = city;
            if (addressLine) document.getElementById('dj-checkout-address').value = addressLine;
        }

        // checkout-map.js is a separate, deferred module script (the Vite
        // include near the top of this file) — it can still be executing
        // when this plain inline script runs, so the actual init call waits
        // for DOMContentLoaded, which fires only after every deferred
        // module script has already run.
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof window.djInitCheckoutMap === 'function') {
                window.djInitCheckoutMap({
                    containerId: 'dj-checkout-map',
                    locale: @json(app()->getLocale()),
                    onPositionChange: function (lat, lng) {
                        document.getElementById('dj-checkout-lat').value = lat;
                        document.getElementById('dj-checkout-lng').value = lng;
                    },
                    onGeocodeStart: function () {
                        djShowCheckoutGeoMsg(@json(__('Detecting address...')), '#8a6b70');
                    },
                    onGeocodeSuccess: function (data) {
                        djFillAddressFromNominatim(data);
                        djShowCheckoutGeoMsg(@json(__("Address filled in below — feel free to adjust it if it's not quite right.")), '#2f7a4d');
                    },
                    onGeocodeError: function () {
                        djShowCheckoutGeoMsg(@json(__('Could not detect an address for this location — please fill it in manually below.')), 'var(--dj-rose-dust)');
                    },
                });
            }
        });

        // "Use My Current Location" — optional convenience only. Manual
        // address fields (and the map above) remain fully sufficient on
        // their own; this never blocks or disables the form if permission
        // is denied or the browser lacks geolocation support. Now also
        // recenters the map and triggers the same reverse-geocode/autofill
        // a manual pin placement does, closing the gap where this button
        // used to only capture raw coordinates.
        document.getElementById('dj-checkout-geolocate').addEventListener('click', function () {
            if (!navigator.geolocation) {
                djShowCheckoutGeoMsg(@json(__('Location services are not supported by your browser.')), 'var(--dj-rose-dust)');
                return;
            }

            djShowCheckoutGeoMsg(@json(__('Detecting your location...')), '#8a6b70');

            navigator.geolocation.getCurrentPosition(
                function (position) {
                    if (typeof window.djCheckoutMapSetPosition === 'function') {
                        window.djCheckoutMapSetPosition(position.coords.latitude, position.coords.longitude);
                    } else {
                        document.getElementById('dj-checkout-lat').value = position.coords.latitude;
                        document.getElementById('dj-checkout-lng').value = position.coords.longitude;
                        djShowCheckoutGeoMsg(@json(__('Location detected successfully.')), '#2f7a4d');
                    }
                },
                function () {
                    djShowCheckoutGeoMsg(@json(__('Could not access your location. Please enter your address manually.')), 'var(--dj-rose-dust)');
                }
            );
        });

        @php
            // Built in a @php block, not inline inside @json() — Blade's
            // directive-argument parser gets confused by a multi-line
            // expression containing a nested trans_field(...) call and
            // silently truncates the compiled output mid-array.
            $djCheckoutTrackingItems = collect($items)->map(fn ($i) => [
                'id' => $i['product']->id,
                'name' => trans_field($i['product'], 'name'),
                'price' => $i['product']->price,
                'quantity' => $i['quantity'],
            ])->values();
        @endphp
        window.djTrack && window.djTrack('begin_checkout', {
            value: djCheckoutSubtotal,
            items: @json($djCheckoutTrackingItems),
        });
    </script>
@endsection
