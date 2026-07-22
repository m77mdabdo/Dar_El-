{{-- Order modification/exchange/return request — expects $order (with
     items and statusHistories eager-loaded), $changeRequestActionUrl (the
     form's POST target: a plain named route for an authenticated owner, or
     a signed URL for a guest — see OrderChangeRequestController::store()),
     and $existingChangeRequest (a pending OrderChangeRequest for this order,
     or null).

     Included once per page (account/orders/show.blade.php,
     account/orders/track via orders/track.blade.php, and the guest
     equivalent) — the fixed ids below are safe on that assumption, same
     convention as partials/back-in-stock-notify.blade.php.

     Inline CSS/JS (not resources/css/app.css or resources/js/app.js) per
     this project's standing deploy-proofing rule: ships correctly on a
     plain git pull, no npm run build + re-zip needed. --}}
@php
    $djOcrWindow = null;
    $djOcrExchangeDeadline = null;

    if ($order->status === 'pending') {
        $djOcrWindow = 'pending';
    } elseif ($order->status === 'delivered') {
        $djOcrDeliveredAt = $order->deliveredAt();
        if ($djOcrDeliveredAt) {
            $djOcrExchangeDeadline = $djOcrDeliveredAt->copy()->addDays(3);
            if (! $djOcrExchangeDeadline->isFuture()) {
                $djOcrWindow = null;
                $djOcrExchangeDeadline = null;
            } else {
                $djOcrWindow = 'delivered';
            }
        }
    }
@endphp

{{-- The trigger button/countdown AND the modal both only ever make sense
     when a window is actually open — with no active window there's nothing
     to click that would open the modal, so skip rendering it at all rather
     than leaving an empty wrapper card on the page. --}}
@if ($djOcrWindow)
    <div class="dj-ocr-section">
        @if ($existingChangeRequest ?? null)
            <p class="dj-ocr-pending-notice">{{ __('order_change_requests.already_pending_notice') }}</p>
        @else
            @if ($djOcrWindow === 'delivered')
                @include('partials.offer-countdown', ['endsAt' => $djOcrExchangeDeadline, 'label' => __('order_change_requests.exchange_window_note')])
            @endif
            <button type="button" class="dj-ocr-trigger" onclick="djOpenChangeRequest('{{ $djOcrWindow }}')">
                {{ $djOcrWindow === 'pending' ? __('order_change_requests.request_change_cancel') : __('order_change_requests.request_exchange_return') }}
            </button>
        @endif
    </div>

<div class="dj-ocr-overlay" id="dj-ocr-overlay" onclick="djCloseChangeRequest()"></div>
<div class="dj-ocr-modal" id="dj-ocr-modal" role="dialog" aria-modal="true" aria-labelledby="dj-ocr-title">
    <button type="button" class="dj-ocr-close" onclick="djCloseChangeRequest()" aria-label="{{ __('Close') }}">&times;</button>

    <div id="dj-ocr-form-area">
        <h3 id="dj-ocr-title"></h3>

        <label class="dj-ocr-label">{{ __('order_change_requests.field_type') }}</label>
        <select id="dj-ocr-type" class="dj-ocr-input"></select>

        @if ($order->items->count() > 1)
            <label class="dj-ocr-label">{{ __('order_change_requests.field_items') }}</label>
            <div id="dj-ocr-items" class="dj-ocr-items">
                @foreach ($order->items as $item)
                    <label class="dj-ocr-item-check">
                        <input type="checkbox" value="{{ $item->id }}">
                        <span>{{ $item->product ? trans_field($item->product, 'name') : $item->product_name }}@if ($item->size) &middot; {{ __('Size') }} {{ $item->size }}@endif</span>
                    </label>
                @endforeach
            </div>
        @endif

        <label class="dj-ocr-label">{{ __('order_change_requests.field_reason') }}</label>
        <select id="dj-ocr-reason" class="dj-ocr-input">
            <option value="">{{ __('order_change_requests.select_reason') }}</option>
            @foreach (\App\Models\OrderChangeRequest::REASONS as $djOcrReason)
                <option value="{{ $djOcrReason }}">{{ __('order_change_requests.reason_'.$djOcrReason) }}</option>
            @endforeach
        </select>

        <div id="dj-ocr-variant-wrap" style="display:none;">
            <label class="dj-ocr-label">{{ __('order_change_requests.field_desired_variant') }}</label>
            <input type="text" id="dj-ocr-variant" class="dj-ocr-input" placeholder="{{ __('order_change_requests.field_desired_variant_placeholder') }}">
        </div>

        <label class="dj-ocr-label">{{ __('order_change_requests.field_notes') }}</label>
        <textarea id="dj-ocr-notes" class="dj-ocr-input" rows="3" placeholder="{{ __('order_change_requests.field_notes_placeholder') }}"></textarea>

        <p id="dj-ocr-error" class="dj-ocr-error" style="display:none;"></p>

        <button type="button" id="dj-ocr-submit" class="dj-ocr-submit" onclick="djSubmitChangeRequest()">{{ __('order_change_requests.submit') }}</button>
    </div>

    <div id="dj-ocr-success-area" class="dj-ocr-success" style="display:none;">
        <div class="dj-ocr-success-icon">✓</div>
        <p id="dj-ocr-success-message"></p>
        <a id="dj-ocr-whatsapp-link" class="dj-ocr-whatsapp-btn" target="_blank" rel="noopener" style="display:none;">
            <svg viewBox="0 0 32 32" fill="currentColor" aria-hidden="true"><path d="M16.001 3C9.096 3 3.5 8.596 3.5 15.5c0 2.348.646 4.54 1.767 6.417L3 29l7.27-2.217A12.42 12.42 0 0 0 16 28.5c6.905 0 12.5-5.596 12.5-12.5S22.906 3 16.001 3Zm7.32 17.688c-.312.878-1.552 1.61-2.532 1.816-.673.14-1.552.253-4.51-.968-3.786-1.564-6.223-5.402-6.412-5.652-.182-.25-1.532-2.038-1.532-3.888 0-1.85.973-2.756 1.32-3.135.312-.34.68-.425.907-.425.227 0 .454.002.652.013.21.011.492-.08.769.586.312.75 1.061 2.6 1.153 2.79.091.19.152.412.03.663-.12.25-.182.406-.363.625-.182.219-.383.489-.546.657-.182.19-.372.396-.16.774.212.378.941 1.552 2.02 2.514 1.388 1.24 2.56 1.623 2.938 1.805.379.181.6.152.82-.091.222-.242.95-1.106 1.204-1.485.253-.379.505-.31.85-.19.348.121 2.196 1.036 2.573 1.224.379.19.63.284.72.442.091.16.091.923-.222 1.8Z"/></svg>
            {{ __('order_change_requests.open_whatsapp') }}
        </a>
    </div>
</div>

<style>
    .dj-ocr-section {
        background: #fff; border-radius: 16px; box-shadow: 0 10px 24px -18px rgba(60,11,23,.2);
        padding: 22px; text-align: center;
    }
    .dj-ocr-pending-notice { font-size: 13px; color: #8a6b70; background: var(--dj-cream); border-radius: 12px; padding: 14px 18px; line-height: 1.7; }
    .dj-ocr-trigger {
        display: inline-flex; align-items: center; gap: 8px; background: transparent; border: 1.5px solid var(--dj-maroon);
        color: var(--dj-maroon); font-weight: 700; font-size: 13.5px; padding: 12px 22px; border-radius: 12px;
        transition: background .2s, color .2s;
    }
    .dj-ocr-trigger:hover { background: var(--dj-maroon); color: var(--dj-gold); }

    .dj-ocr-overlay { position: fixed; inset: 0; background: rgba(20,5,9,.55); z-index: 290; opacity: 0; visibility: hidden; transition: opacity .25s, visibility .25s; }
    .dj-ocr-overlay.dj-open { opacity: 1; visibility: visible; }
    .dj-ocr-modal {
        position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) scale(.96); z-index: 300;
        width: min(460px, 92vw); max-height: 88vh; overflow-y: auto; background: #fff; border-radius: 20px;
        box-shadow: var(--dj-shadow); padding: 30px 26px 26px; opacity: 0; visibility: hidden; transition: opacity .25s, visibility .25s, transform .25s;
    }
    .dj-ocr-modal.dj-open { opacity: 1; visibility: visible; transform: translate(-50%, -50%) scale(1); }
    .dj-ocr-close {
        position: absolute; top: 14px; inset-inline-end: 14px; width: 32px; height: 32px; border-radius: 50%;
        background: var(--dj-cream-2); color: var(--dj-maroon); font-size: 18px; line-height: 1;
        display: flex; align-items: center; justify-content: center; transition: background .2s;
    }
    .dj-ocr-close:hover { background: var(--dj-gold); }
    #dj-ocr-title { font-family: 'Tajawal'; font-weight: 700; font-size: 19px; color: var(--dj-maroon); margin-bottom: 18px; padding-inline-end: 26px; }
    body.dj-en #dj-ocr-title { font-family: 'Playfair Display'; }

    .dj-ocr-label { display: block; font-size: 12.5px; font-weight: 700; color: var(--dj-maroon); margin-bottom: 6px; }
    .dj-ocr-input {
        width: 100%; padding: 12px 14px; border: 1.5px solid var(--dj-cream-2); border-radius: 10px;
        font-family: inherit; font-size: 13.5px; background: var(--dj-cream); color: var(--dj-ink); margin-bottom: 16px;
    }
    .dj-ocr-input:focus { outline: none; border-color: var(--dj-maroon); }
    textarea.dj-ocr-input { resize: vertical; }

    .dj-ocr-items { margin-bottom: 16px; display: flex; flex-direction: column; gap: 8px; }
    .dj-ocr-item-check {
        display: flex; align-items: center; gap: 10px; font-size: 13px; color: var(--dj-ink);
        background: var(--dj-cream); border-radius: 10px; padding: 10px 12px; cursor: pointer;
    }
    .dj-ocr-item-check input { width: 16px; height: 16px; accent-color: var(--dj-maroon); flex-shrink: 0; }

    .dj-ocr-error { font-size: 12.5px; color: var(--dj-rose-dust); margin: -8px 0 14px; line-height: 1.6; }

    .dj-ocr-submit {
        width: 100%; background: var(--dj-maroon); color: var(--dj-gold); font-weight: 700; padding: 13px;
        border-radius: 12px; font-size: 14px; transition: background .2s;
    }
    .dj-ocr-submit:hover { background: var(--dj-maroon-dark); }
    .dj-ocr-submit:disabled { opacity: .6; }

    .dj-ocr-success { text-align: center; padding: 10px 4px; }
    .dj-ocr-success-icon {
        width: 56px; height: 56px; border-radius: 50%; background: rgba(47,122,77,.12); color: #2f7a4d;
        display: flex; align-items: center; justify-content: center; font-size: 26px; font-weight: 800; margin: 0 auto 16px;
    }
    #dj-ocr-success-message { font-size: 14px; color: var(--dj-ink); line-height: 1.8; margin-bottom: 18px; }
    .dj-ocr-whatsapp-btn {
        display: inline-flex; align-items: center; gap: 8px; background: #25D366; color: #fff; font-weight: 700;
        font-size: 13.5px; padding: 13px 24px; border-radius: 12px; transition: background .2s;
    }
    .dj-ocr-whatsapp-btn:hover { background: #1ea952; }
    .dj-ocr-whatsapp-btn svg { width: 18px; height: 18px; flex-shrink: 0; }

    @media (max-width: 480px) {
        .dj-ocr-modal { padding: 26px 18px 22px; }
    }
</style>

<script>
(function () {
    var overlay = document.getElementById('dj-ocr-overlay');
    var modal = document.getElementById('dj-ocr-modal');
    var titleEl = document.getElementById('dj-ocr-title');
    var typeSelect = document.getElementById('dj-ocr-type');
    var reasonSelect = document.getElementById('dj-ocr-reason');
    var variantWrap = document.getElementById('dj-ocr-variant-wrap');
    var variantInput = document.getElementById('dj-ocr-variant');
    var notesInput = document.getElementById('dj-ocr-notes');
    var errorEl = document.getElementById('dj-ocr-error');
    var submitBtn = document.getElementById('dj-ocr-submit');
    var formArea = document.getElementById('dj-ocr-form-area');
    var successArea = document.getElementById('dj-ocr-success-area');
    var successMessage = document.getElementById('dj-ocr-success-message');
    var whatsappLink = document.getElementById('dj-ocr-whatsapp-link');

    if (!overlay || !modal) return;

    var actionUrl = @json($changeRequestActionUrl ?? null);
    var currentWindow = null;

    var typeOptions = {
        pending: [
            ['modify', @json(__('order_change_requests.type_modify'))],
            ['cancel', @json(__('order_change_requests.type_cancel'))],
        ],
        delivered: [
            ['exchange', @json(__('order_change_requests.type_exchange'))],
            ['return', @json(__('order_change_requests.type_return'))],
        ],
    };

    var i18n = {
        titlePending: @json(__('order_change_requests.modal_title_pending')),
        titleDelivered: @json(__('order_change_requests.modal_title_delivered')),
        submit: @json(__('order_change_requests.submit')),
        submitting: @json(__('order_change_requests.submitting')),
        genericError: @json(__('order_change_requests.generic_error')),
    };

    function resetForm() {
        formArea.style.display = '';
        successArea.style.display = 'none';
        errorEl.style.display = 'none';
        reasonSelect.value = '';
        variantInput.value = '';
        notesInput.value = '';
        variantWrap.style.display = 'none';
        document.querySelectorAll('#dj-ocr-items input[type="checkbox"]').forEach(function (cb) { cb.checked = false; });
        submitBtn.disabled = false;
        submitBtn.textContent = i18n.submit;
    }

    window.djOpenChangeRequest = function (windowType) {
        currentWindow = windowType;
        resetForm();

        titleEl.textContent = windowType === 'pending' ? i18n.titlePending : i18n.titleDelivered;

        typeSelect.innerHTML = '';
        typeOptions[windowType].forEach(function (opt) {
            var option = document.createElement('option');
            option.value = opt[0];
            option.textContent = opt[1];
            typeSelect.appendChild(option);
        });
        variantWrap.style.display = windowType === 'delivered' && typeSelect.value === 'exchange' ? '' : 'none';

        overlay.classList.add('dj-open');
        modal.classList.add('dj-open');
    };

    window.djCloseChangeRequest = function () {
        overlay.classList.remove('dj-open');
        modal.classList.remove('dj-open');
    };

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('dj-open')) window.djCloseChangeRequest();
    });

    typeSelect.addEventListener('change', function () {
        variantWrap.style.display = (currentWindow === 'delivered' && typeSelect.value === 'exchange') ? '' : 'none';
    });

    function showError(text) {
        errorEl.textContent = text;
        errorEl.style.display = '';
    }

    window.djSubmitChangeRequest = function () {
        if (!actionUrl) {
            showError(i18n.genericError);
            return;
        }

        if (!reasonSelect.value) {
            showError(@json(__('order_change_requests.select_reason')));
            return;
        }

        var itemIds = Array.from(document.querySelectorAll('#dj-ocr-items input[type="checkbox"]:checked')).map(function (cb) {
            return parseInt(cb.value, 10);
        });

        submitBtn.disabled = true;
        submitBtn.textContent = i18n.submitting;
        errorEl.style.display = 'none';

        fetch(actionUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify({
                type: typeSelect.value,
                order_item_ids: itemIds.length ? itemIds : null,
                reason: reasonSelect.value,
                notes: notesInput.value || null,
                desired_variant: variantInput.value || null,
            }),
        })
            .then(function (res) {
                return res.json().then(function (data) { return { ok: res.ok, data: data }; });
            })
            .then(function (result) {
                if (result.ok) {
                    formArea.style.display = 'none';
                    successArea.style.display = '';
                    successMessage.textContent = result.data.message;
                    if (result.data.whatsapp_url) {
                        whatsappLink.href = result.data.whatsapp_url;
                        whatsappLink.style.display = '';
                    } else {
                        whatsappLink.style.display = 'none';
                    }
                } else {
                    var errors = result.data.errors || {};
                    var firstError = Object.values(errors)[0];
                    showError((firstError && firstError[0]) || i18n.genericError);
                    submitBtn.disabled = false;
                    submitBtn.textContent = i18n.submit;
                }
            })
            .catch(function () {
                showError(i18n.genericError);
                submitBtn.disabled = false;
                submitBtn.textContent = i18n.submit;
            });
    };
})();
</script>
@endif
