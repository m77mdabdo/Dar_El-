<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Verify Your Account') }} — Dar El Jamila</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Aref+Ruqaa:wght@400;700&family=Tajawal:wght@300;400;500;700;900&family=Playfair+Display:ital,wght@0,500;0,700;1,500&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="preload" as="image" href="https://images.unsplash.com/photo-1772474528936-4f1187eb1611?w=1600&q=85&auto=format&fit=crop">
    @include('partials.favicon-links')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="dj-login-page {{ app()->getLocale() === 'en' ? 'dj-en' : '' }}">

    <div class="dj-login-bg" style="background-image:url('https://images.unsplash.com/photo-1772474528936-4f1187eb1611?w=1600&q=85&auto=format&fit=crop');"></div>
    <div class="dj-login-overlay"></div>
    <div class="dj-login-lattice dj-lattice-bg"></div>

    <div class="dj-login-card">
        <div class="dj-login-brand">
            <x-brand-logo style="height:44px;width:auto;margin-inline:auto;" />
            <div class="dj-login-tagline">{{ __('Timeless Elegance. Crafted for You.') }}</div>
        </div>

        <div class="dj-login-heading">
            <h1>{{ __('Verify Your Account') }}</h1>
            <p>{{ __('Enter the 6-digit code sent to') }} <strong>{{ $email }}</strong></p>
        </div>

        @if (session('status'))
            <div class="dj-login-status" id="dj-otp-status">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('otp.verify') }}" id="dj-otp-form">
            @csrf
            <input type="hidden" name="otp" id="dj-otp-hidden">

            <div class="dj-otp-boxes" dir="ltr">
                @for ($i = 0; $i < 6; $i++)
                    <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1"
                           class="dj-otp-input" data-index="{{ $i }}"
                           autocomplete="{{ $i === 0 ? 'one-time-code' : 'off' }}"
                           {{ $i === 0 ? 'autofocus' : '' }}>
                @endfor
            </div>

            @error('otp')
                <p class="dj-field-error dj-otp-error">{{ $message }}</p>
            @enderror

            <button type="submit" class="dj-login-submit" id="dj-otp-submit">
                <span id="dj-otp-submit-label">{{ __('Verify') }}</span>
            </button>
        </form>

        <form method="POST" action="{{ route('otp.resend') }}" id="dj-otp-resend-form" class="dj-otp-resend-form">
            @csrf
            <p class="dj-otp-resend-row">
                <span id="dj-otp-timer-text">
                    {{ __('Resend code in :seconds s', ['seconds' => $resendCooldown]) }}
                </span>
                <button type="submit" class="dj-otp-resend-btn" id="dj-otp-resend-btn" disabled>
                    <span id="dj-otp-resend-label">{{ __('Resend OTP') }}</span>
                </button>
            </p>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="dj-login-back dj-otp-back-btn">← {{ __('Back to Login') }}</button>
        </form>
    </div>

    <script>
        (function () {
            var boxes = Array.prototype.slice.call(document.querySelectorAll('.dj-otp-input'));
            var hidden = document.getElementById('dj-otp-hidden');
            var form = document.getElementById('dj-otp-form');
            var submitBtn = document.getElementById('dj-otp-submit');
            var submitLabel = document.getElementById('dj-otp-submit-label');

            function syncHidden() {
                hidden.value = boxes.map(function (b) { return b.value; }).join('');
            }

            boxes.forEach(function (box, index) {
                box.addEventListener('input', function () {
                    box.value = box.value.replace(/[^0-9]/g, '').slice(0, 1);
                    syncHidden();
                    if (box.value && index < boxes.length - 1) {
                        boxes[index + 1].focus();
                    }
                    if (index === boxes.length - 1 && box.value) {
                        syncHidden();
                        if (hidden.value.length === 6) {
                            form.requestSubmit();
                        }
                    }
                });

                box.addEventListener('keydown', function (e) {
                    if (e.key === 'Backspace' && !box.value && index > 0) {
                        boxes[index - 1].focus();
                    }
                });

                box.addEventListener('paste', function (e) {
                    e.preventDefault();
                    var pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '');
                    if (!pasted) return;
                    pasted.split('').slice(0, boxes.length).forEach(function (digit, i) {
                        if (boxes[i]) boxes[i].value = digit;
                    });
                    syncHidden();
                    var nextEmpty = boxes.findIndex(function (b) { return !b.value; });
                    boxes[nextEmpty === -1 ? boxes.length - 1 : nextEmpty].focus();
                    if (hidden.value.length === 6) {
                        form.requestSubmit();
                    }
                });
            });

            form.addEventListener('submit', function () {
                syncHidden();
                submitBtn.disabled = true;
                submitLabel.innerHTML = '<span class="dj-login-spinner" aria-hidden="true"></span>{{ __('Verify') }}';
            });

            // Resend countdown timer
            var remaining = {{ (int) $resendCooldown }};
            var timerText = document.getElementById('dj-otp-timer-text');
            var resendBtn = document.getElementById('dj-otp-resend-btn');
            var resendLabel = document.getElementById('dj-otp-resend-label');

            function tick() {
                if (remaining <= 0) {
                    timerText.style.display = 'none';
                    resendBtn.disabled = false;
                    return;
                }
                timerText.textContent = '{{ __('Resend code in') }} ' + remaining + 's';
                remaining -= 1;
                setTimeout(tick, 1000);
            }
            tick();

            var resendForm = document.getElementById('dj-otp-resend-form');
            resendForm.addEventListener('submit', function () {
                resendBtn.disabled = true;
                resendLabel.innerHTML = '<span class="dj-login-spinner" aria-hidden="true"></span>{{ __('Resend OTP') }}';
            });
        })();
    </script>
</body>
</html>
