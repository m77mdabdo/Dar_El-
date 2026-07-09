<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
<meta charset="utf-8">
<title>{{ __('invoice.invoice') }} {{ $invoiceNumber }}</title>
<style>
    @php
        $djCairoRegular = base64_encode(file_get_contents(resource_path('fonts/cairo/Cairo-Regular.ttf')));
        $djCairoSemiBold = base64_encode(file_get_contents(resource_path('fonts/cairo/Cairo-SemiBold.ttf')));
        $djCairoBold = base64_encode(file_get_contents(resource_path('fonts/cairo/Cairo-Bold.ttf')));
    @endphp
    @font-face {
        font-family: 'Cairo';
        font-weight: 400;
        font-style: normal;
        src: url(data:font/truetype;charset=utf-8;base64,{{ $djCairoRegular }}) format('truetype');
    }
    @font-face {
        font-family: 'Cairo';
        font-weight: 600;
        font-style: normal;
        src: url(data:font/truetype;charset=utf-8;base64,{{ $djCairoSemiBold }}) format('truetype');
    }
    @font-face {
        font-family: 'Cairo';
        font-weight: 700;
        font-style: normal;
        src: url(data:font/truetype;charset=utf-8;base64,{{ $djCairoBold }}) format('truetype');
    }

    :root {
        --dj-primary: #5B1024;
        --dj-primary-dark: #3C0B17;
        --dj-secondary: #F7F0E7;
        --dj-gold: #E8C39A;
        --dj-ink: #2A1015;
        --dj-muted: #9C5064;
        --dj-border: #EAD9C4;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    @page { size: A4; margin: 0; }

    html, body {
        font-family: 'Cairo', 'Segoe UI', Arial, sans-serif;
        font-size: 13px;
        color: var(--dj-ink);
        background: #ffffff;
        direction: {{ $isRtl ? 'rtl' : 'ltr' }};
        unicode-bidi: embed;
        -webkit-font-smoothing: antialiased;
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }

    .text-start { text-align: {{ $isRtl ? 'right' : 'left' }}; }
    .text-end { text-align: {{ $isRtl ? 'left' : 'right' }}; }
    .muted { color: var(--dj-muted); }

    .page { padding: 26mm 15mm 18mm; }

    /*
     * No box-shadow anywhere in this file, deliberately: Chrome's
     * page.pdf() print pipeline (used by Browsershot) renders
     * box-shadow + border-radius combinations as a broken, hard-edged
     * rectangular smear instead of a soft blur — confirmed by
     * screenshotting the exact same markup on-screen (renders perfectly)
     * vs printing it to PDF (visibly broken). Depth is conveyed with
     * borders + subtle background tints instead, which is closer to
     * Stripe's actual flat invoice style anyway.
     */

    /* ----- Header ----- */
    .header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        background: linear-gradient(135deg, var(--dj-primary) 0%, var(--dj-primary-dark) 100%);
        color: #fff;
        border-radius: 16px;
        padding: 28px 32px;
    }
    .brand-mark { font-size: 26px; font-weight: 700; letter-spacing: .5px; color: var(--dj-gold); }
    .brand-tagline { font-size: 11px; color: rgba(247,240,231,0.8); margin-top: 6px; letter-spacing: .3px; }
    .header-right { text-align: {{ $isRtl ? 'left' : 'right' }}; }
    .invoice-title { font-size: 20px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: #fff; }
    .invoice-number { font-size: 13px; color: var(--dj-gold); font-weight: 600; margin-top: 6px; }
    .invoice-date { font-size: 11.5px; color: rgba(247,240,231,0.75); margin-top: 4px; }

    /* ----- Status + order number strip ----- */
    .strip {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 18px;
        padding: 14px 20px;
        background: var(--dj-secondary);
        border-radius: 12px;
    }
    .strip-item { display: flex; flex-direction: column; gap: 3px; }
    .strip-label { font-size: 10px; text-transform: uppercase; letter-spacing: .6px; color: var(--dj-muted); font-weight: 600; }
    .strip-value { font-size: 13px; font-weight: 700; color: var(--dj-primary); }
    .status-badge {
        display: inline-block;
        padding: 6px 16px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .3px;
        background: var(--dj-primary);
        color: #fff;
    }

    /* ----- Address cards ----- */
    .cards-row { display: flex; gap: 16px; margin-top: 22px; }
    .card {
        flex: 1;
        background: #fff;
        border: 1px solid var(--dj-border);
        border-radius: 14px;
        padding: 18px 20px;
    }
    .card-label {
        font-size: 10.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: var(--dj-primary);
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .card-label .dot { width: 6px; height: 6px; border-radius: 50%; background: var(--dj-gold); display: inline-block; }
    .card-line { font-size: 12.5px; line-height: 1.75; color: var(--dj-ink); }
    .card-line.strong { font-weight: 700; }
    .card-line.muted { color: var(--dj-muted); font-size: 11.5px; }

    /* ----- Product table ----- */
    .section-title {
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: var(--dj-primary);
        margin: 26px 0 10px;
    }
    .items {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        border-radius: 14px;
        overflow: hidden;
        border: 1px solid var(--dj-border);
    }
    .items thead th {
        background: var(--dj-primary);
        color: #fff;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .4px;
        padding: 12px 14px;
    }
    .items tbody td {
        padding: 12px 14px;
        border-bottom: 1px solid var(--dj-border);
        font-size: 12.5px;
        vertical-align: middle;
        background: #fff;
    }
    .items tbody tr:last-child td { border-bottom: none; }
    .items tbody tr:nth-child(even) td { background: #FCFAF6; }
    .item-thumb {
        width: 42px; height: 42px; border-radius: 8px; object-fit: cover;
        border: 1px solid var(--dj-border); vertical-align: middle;
    }
    .item-name { font-weight: 700; color: var(--dj-ink); font-size: 12.5px; }
    .item-meta { color: var(--dj-muted); font-size: 10.5px; margin-top: 2px; }

    /* ----- Totals ----- */
    .totals-wrap { display: flex; justify-content: {{ $isRtl ? 'flex-start' : 'flex-end' }}; margin-top: 18px; }
    .totals-card {
        width: 280px;
        background: var(--dj-secondary);
        border-radius: 14px;
        padding: 18px 20px;
    }
    .totals-row { display: flex; justify-content: space-between; padding: 5px 0; font-size: 12.5px; }
    .totals-row .label { color: var(--dj-muted); }
    .totals-row .value { color: var(--dj-ink); font-weight: 600; }
    .totals-divider { height: 1px; background: var(--dj-border); margin: 8px 0; }
    .grand-total-row { display: flex; justify-content: space-between; align-items: center; padding-top: 4px; }
    .grand-total-label { font-size: 13px; font-weight: 700; color: var(--dj-primary); }
    .grand-total-value { font-size: 19px; font-weight: 700; color: var(--dj-primary); }

    /* ----- Meta grid (payment/status) ----- */
    .meta-grid { display: flex; gap: 16px; margin-top: 18px; }
    .meta-box {
        flex: 1;
        background: #fff;
        border: 1px solid var(--dj-border);
        border-radius: 14px;
        padding: 14px 18px;
    }
    .meta-box .label { font-size: 10px; text-transform: uppercase; letter-spacing: .5px; color: var(--dj-muted); font-weight: 700; }
    .meta-box .value { font-size: 13px; font-weight: 700; color: var(--dj-ink); margin-top: 4px; }

    .notes-box {
        margin-top: 16px;
        background: var(--dj-secondary);
        border-{{ $isRtl ? 'right' : 'left' }}: 3px solid var(--dj-primary);
        border-radius: 10px;
        padding: 14px 18px;
        font-size: 12px;
        color: var(--dj-ink);
        line-height: 1.7;
    }

    /* ----- Footer ----- */
    .footer {
        margin-top: 34px;
        padding-top: 18px;
        border-top: 1px solid var(--dj-border);
        text-align: center;
    }
    .footer-brand { font-size: 13px; font-weight: 700; color: var(--dj-primary); }
    .footer-line { font-size: 10.5px; color: var(--dj-muted); margin-top: 5px; line-height: 1.7; }
</style>
</head>
<body>
<div class="page">

    <div class="header">
        <div>
            <div class="brand-mark">{{ __('Dar El-Jamila') }}</div>
            <div class="brand-tagline">{{ __('invoice.tagline') }}</div>
        </div>
        <div class="header-right">
            <div class="invoice-title">{{ __('invoice.invoice') }}</div>
            <div class="invoice-number">{{ $invoiceNumber }}</div>
            <div class="invoice-date">{{ $order->created_at->translatedFormat('F j, Y') }}</div>
        </div>
    </div>

    <div class="strip">
        <div class="strip-item">
            <span class="strip-label">{{ __('invoice.order_number') }}</span>
            <span class="strip-value">{{ $order->order_number }}</span>
        </div>
        <div class="strip-item" style="text-align:center;">
            <span class="strip-label">{{ __('invoice.order_status') }}</span>
            <span class="status-badge">{{ __('orders.status_'.$order->status) }}</span>
        </div>
        <div class="strip-item" style="text-align:{{ $isRtl ? 'left' : 'right' }};">
            <span class="strip-label">{{ __('invoice.date') }}</span>
            <span class="strip-value">{{ $order->created_at->translatedFormat('F j, Y') }}</span>
        </div>
    </div>

    <div class="cards-row">
        <div class="card">
            <div class="card-label"><span class="dot"></span>{{ __('invoice.bill_to') }}</div>
            <div class="card-line strong">{{ $order->customer_name }}</div>
            <div class="card-line muted">{{ $order->customer_email }}</div>
            <div class="card-line muted">{{ $order->customer_phone }}</div>
        </div>
        <div class="card">
            <div class="card-label"><span class="dot"></span>{{ __('invoice.ship_to') }}</div>
            <div class="card-line strong">{{ $order->customer_name }}</div>
            <div class="card-line">{{ $order->address }}</div>
            <div class="card-line muted">{{ $order->city }}, {{ $order->governorate }}</div>
        </div>
    </div>

    <div class="section-title">{{ __('invoice.item') }}</div>
    <table class="items">
        <thead>
            <tr>
                <th class="text-start" style="width:44%;">{{ __('invoice.item') }}</th>
                <th class="text-start">{{ __('invoice.sku') }}</th>
                <th class="text-start">{{ __('invoice.size') }}</th>
                <th class="text-end">{{ __('invoice.quantity') }}</th>
                <th class="text-end">{{ __('invoice.price') }}</th>
                <th class="text-end">{{ __('invoice.total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                @php
                    $djItemName = $isRtl ? ($item->product?->name_ar ?: $item->product_name) : ($item->product?->name_en ?: $item->product_name);
                    $djItemImage = $item->product?->cover_image_src;
                @endphp
                <tr>
                    <td class="text-start">
                        <table style="width:100%; border-collapse:collapse;"><tr>
                            <td style="width:50px; padding:0;">
                                @if ($djItemImage)
                                    <img src="{{ $djItemImage }}" class="item-thumb" alt="">
                                @else
                                    <div class="item-thumb" style="background:#F1E4D3;"></div>
                                @endif
                            </td>
                            <td style="padding:0 0 0 10px;">
                                <div class="item-name">{{ $djItemName }}</div>
                            </td>
                        </tr></table>
                    </td>
                    <td class="text-start">{{ $item->product?->sku ?? '—' }}</td>
                    <td class="text-start">{{ $item->size ?? '—' }}</td>
                    <td class="text-end">{{ $item->quantity }}</td>
                    <td class="text-end">{{ number_format($item->price) }} EGP</td>
                    <td class="text-end" style="font-weight:700;">{{ number_format($item->price * $item->quantity) }} EGP</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals-wrap">
        <div class="totals-card">
            <div class="totals-row">
                <span class="label">{{ __('invoice.subtotal') }}</span>
                <span class="value">{{ number_format($order->subtotal) }} EGP</span>
            </div>
            @if ($order->discount_amount > 0)
                <div class="totals-row">
                    <span class="label">{{ __('invoice.discount') }}@if($order->coupon_code) ({{ $order->coupon_code }})@endif</span>
                    <span class="value">-{{ number_format($order->discount_amount) }} EGP</span>
                </div>
            @endif
            <div class="totals-row">
                <span class="label">{{ __('invoice.shipping') }}</span>
                <span class="value">{{ number_format($order->shipping_fee) }} EGP</span>
            </div>
            <div class="totals-divider"></div>
            <div class="grand-total-row">
                <span class="grand-total-label">{{ __('invoice.grand_total') }}</span>
                <span class="grand-total-value">{{ number_format($order->total) }} EGP</span>
            </div>
        </div>
    </div>

    <div class="meta-grid">
        <div class="meta-box">
            <div class="label">{{ __('invoice.payment_method') }}</div>
            <div class="value">{{ $order->payment_method === 'cod' ? __('invoice.payment_method_cod') : $order->payment_method }}</div>
        </div>
        @if ($order->shippingMethod)
            <div class="meta-box">
                <div class="label">{{ __('invoice.shipping') }}</div>
                <div class="value">{{ $isRtl ? $order->shippingMethod->name_ar : $order->shippingMethod->name_en }}</div>
            </div>
        @endif
    </div>

    @if ($order->notes)
        <div class="notes-box">
            <strong>{{ __('invoice.notes') }}:</strong> {{ $order->notes }}
        </div>
    @endif

    <div class="footer">
        <div class="footer-brand">{{ __('Dar El-Jamila') }}</div>
        <div class="footer-line">{{ __('invoice.thank_you') }}</div>
        @if ($djSupportEmail ?? null)
            <div class="footer-line">{{ __('invoice.contact_support', ['email' => $djSupportEmail]) }}</div>
        @endif
        @if ($djWhatsapp ?? null)
            <div class="footer-line" dir="ltr">{{ $djWhatsapp }}</div>
        @endif
        <div class="footer-line">&copy; {{ now()->year }} {{ __('Dar El-Jamila') }}</div>
    </div>

</div>
</body>
</html>
