{{-- Inline (not resources/css/app.css or resources/js/app.js) so this ships
     the moment this file reaches production via a plain git pull — this
     deploy process has repeatedly been observed running for a while on a
     compiled asset bundle that predates the current commit, with no
     npm run build in between (see the WhatsApp button, shop size-filter,
     and navbar search fixes earlier this project). --}}
<style>
    .dj-size-wrap { position: relative; display: inline-flex; }
    .dj-size-bell {
        position: absolute; top: -6px; right: -6px; width: 20px; height: 20px; border-radius: 50%;
        background: var(--dj-maroon); color: var(--dj-gold); font-size: 10px; line-height: 1; padding: 0;
        display: flex; align-items: center; justify-content: center; border: 2px solid #fff; cursor: pointer;
    }
    body.dj-en .dj-size-bell { right: auto; left: -6px; }

    .dj-notify-overlay {
        position: fixed; inset: 0; background: rgba(20,5,9,.55); z-index: 190;
        opacity: 0; visibility: hidden; transition: opacity .25s, visibility .25s;
    }
    .dj-notify-overlay.dj-open { opacity: 1; visibility: visible; }

    .dj-notify-panel {
        position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) scale(.94);
        width: min(360px, 92vw); background: #fff; border-radius: 18px; box-shadow: var(--dj-shadow);
        padding: 32px 24px 26px; z-index: 200; opacity: 0; visibility: hidden; text-align: center;
        transition: opacity .25s, visibility .25s, transform .25s;
    }
    .dj-notify-panel.dj-open { opacity: 1; visibility: visible; transform: translate(-50%, -50%) scale(1); }

    .dj-notify-close {
        position: absolute; top: 10px; left: 12px; background: transparent; color: var(--dj-rose-dust);
        font-size: 17px; min-width: 34px; min-height: 34px; display: flex; align-items: center; justify-content: center;
    }
    body.dj-en .dj-notify-close { left: auto; right: 12px; }

    .dj-notify-icon { font-size: 28px; margin-bottom: 6px; }
    .dj-notify-label { font-size: 14.5px; color: var(--dj-maroon); font-weight: 700; margin: 0 0 18px; line-height: 1.6; }

    .dj-notify-input {
        width: 100%; padding: 12px 14px; border: 1.5px solid var(--dj-cream-2); border-radius: 12px;
        font-family: inherit; font-size: 14px; background: var(--dj-cream); color: var(--dj-ink); margin-bottom: 12px;
    }
    .dj-notify-input:focus { outline: none; border-color: var(--dj-maroon); }

    .dj-notify-submit {
        width: 100%; background: var(--dj-maroon); color: var(--dj-gold); font-weight: 700; padding: 13px;
        border-radius: 12px; font-size: 14px; transition: background .2s;
    }
    .dj-notify-submit:hover { background: var(--dj-maroon-dark); }
    .dj-notify-submit:disabled { opacity: .6; }

    .dj-notify-message { font-size: 13.5px; line-height: 1.7; color: var(--dj-maroon); padding: 6px 0 2px; }
    .dj-notify-message.dj-notify-error { color: var(--dj-rose-dust); }
</style>

<div class="dj-notify-overlay" id="dj-notify-overlay"></div>
<div class="dj-notify-panel" id="dj-notify-panel" role="dialog" aria-modal="true" aria-labelledby="dj-notify-label">
    <button type="button" class="dj-notify-close" id="dj-notify-close" aria-label="{{ __('Close') }}">✕</button>
    <div class="dj-notify-icon">🔔</div>
    <p class="dj-notify-label" id="dj-notify-label"></p>
    <div id="dj-notify-form-area">
        <input type="email" id="dj-notify-email" class="dj-notify-input" placeholder="{{ __('Your email address') }}"
               value="{{ auth()->user()->email ?? '' }}" autocomplete="email">
        <button type="button" class="dj-notify-submit" id="dj-notify-submit">{{ __('Notify me') }}</button>
    </div>
    <div id="dj-notify-message" class="dj-notify-message" style="display:none;"></div>
</div>

<script>
(function () {
    var overlay = document.getElementById('dj-notify-overlay');
    var panel = document.getElementById('dj-notify-panel');
    var closeBtn = document.getElementById('dj-notify-close');
    var labelEl = document.getElementById('dj-notify-label');
    var formArea = document.getElementById('dj-notify-form-area');
    var emailInput = document.getElementById('dj-notify-email');
    var submitBtn = document.getElementById('dj-notify-submit');
    var messageEl = document.getElementById('dj-notify-message');

    if (!overlay || !panel) return;

    var storeUrl = @json(route('back-in-stock.store', $product));
    var productName = @json(trans_field($product, 'name'));
    var sizeWord = @json(__('Size'));
    var notifyLabel = @json(__('Notify me'));
    var i18n = {
        sending: @json(__('Sending...')),
        invalidEmail: @json(__('Please enter a valid email address.')),
        genericError: @json(__('Something went wrong. Please try again.')),
        pushOptinMessage: @json(__('Want a push notification when ":name" is back, too? No need to check your email.')),
    };

    var currentSizeId = null;

    function resetPanel() {
        formArea.style.display = '';
        messageEl.style.display = 'none';
        emailInput.disabled = false;
        submitBtn.disabled = false;
        submitBtn.textContent = notifyLabel;
    }

    window.djOpenNotifyMe = function (sizeId, sizeLabel) {
        currentSizeId = sizeId;
        labelEl.textContent = sizeId ? (productName + ' — ' + sizeWord + ' ' + sizeLabel) : productName;
        resetPanel();

        overlay.classList.add('dj-open');
        panel.classList.add('dj-open');
        setTimeout(function () { emailInput.focus(); }, 60);
    };

    window.djCloseNotifyMe = function () {
        overlay.classList.remove('dj-open');
        panel.classList.remove('dj-open');
    };

    overlay.addEventListener('click', window.djCloseNotifyMe);
    closeBtn.addEventListener('click', window.djCloseNotifyMe);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && panel.classList.contains('dj-open')) window.djCloseNotifyMe();
    });

    function showMessage(text, isError) {
        messageEl.textContent = text;
        messageEl.className = 'dj-notify-message' + (isError ? ' dj-notify-error' : '');
        messageEl.style.display = '';
    }

    submitBtn.addEventListener('click', function () {
        var email = emailInput.value.trim();
        if (!email || email.indexOf('@') === -1 || email.indexOf('.') === -1) {
            showMessage(i18n.invalidEmail, true);
            return;
        }

        submitBtn.disabled = true;
        emailInput.disabled = true;
        submitBtn.textContent = i18n.sending;
        messageEl.style.display = 'none';

        fetch(storeUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify({ email: email, product_size_id: currentSizeId }),
        })
            .then(function (res) {
                return res.json().then(function (data) { return { ok: res.ok, data: data }; });
            })
            .then(function (result) {
                if (result.ok) {
                    formArea.style.display = 'none';
                    showMessage(result.data.message, false);
                    // Contextual push opt-in — only ever asked right after a
                    // customer has just shown real interest in an alert for
                    // this exact product, never on page load. See
                    // djShowPushOptinBanner() in app.js for why/how. This
                    // modal's own overlay sits above the banner (it needs to
                    // cover the whole page while open), so it's closed first
                    // — after a moment to actually read the confirmation
                    // message above — or the banner would be shown but
                    // unreachable behind it.
                    if (window.djShowPushOptinBanner) {
                        setTimeout(function () {
                            window.djCloseNotifyMe();
                            window.djShowPushOptinBanner(
                                i18n.pushOptinMessage.replace(':name', productName),
                                result.data.push_link_token
                            );
                        }, 1600);
                    }
                } else {
                    var msg = (result.data.errors && result.data.errors.email && result.data.errors.email[0]) || i18n.genericError;
                    showMessage(msg, true);
                    submitBtn.disabled = false;
                    emailInput.disabled = false;
                    submitBtn.textContent = notifyLabel;
                }
            })
            .catch(function () {
                showMessage(i18n.genericError, true);
                submitBtn.disabled = false;
                emailInput.disabled = false;
                submitBtn.textContent = notifyLabel;
            });
    });
})();
</script>
