<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
<meta charset="UTF-8">
<title>{{ __('invoice.invoice') }} {{ $invoiceNumber }}</title>
<style>
    /*
     * mPDF-specific template — deliberately not sharing CSS with
     * invoices/pdf.blade.php (the dompdf version, kept as a rollback
     * engine). mPDF's CSS support differs enough that reusing the same
     * stylesheet would mean designing for the lowest common denominator
     * of both engines:
     *   - No CSS custom properties (var(--x)) — mPDF's CSS parser doesn't
     *     support them, so every color below is a literal hex value.
     *   - Page margins come from the Mpdf constructor config
     *     (InvoiceMpdfRenderer), not a CSS .page wrapper with padding —
     *     mPDF's own margin handling is reliable, so there's no dompdf-style
     *     "26mm of padding accidentally pushes everything to page 2" trap.
     *   - RTL <table> column order: mPDF reverses it correctly and
     *     natively (verified empirically by rendering a real 2-column RTL
     *     table and inspecting the output) — unlike dompdf, there is no
     *     need to force dir="ltr" on every table and manually swap which
     *     content goes in which cell. Markup below is written in plain
     *     natural reading order.
     *   - Every bordered/background "box" (address cards, totals box,
     *     payment/shipping boxes) has its border+background+padding on
     *     the <td> itself, never on a <div> nested inside the <td>. A
     *     <div> with its own border/padding, nested in a table cell,
     *     containing a smaller-font child (e.g. a 9.5px label above
     *     11px body text) made mPDF miscalculate that child's line-box
     *     position — the smaller text rendered visually overlapping the
     *     div's own border. Verified by isolating it: identical markup
     *     with the border/padding moved from a nested div onto the <td>
     *     renders correctly; reverting back to a nested div reproduces
     *     the overlap every time. Plain text (no font-size change) or a
     *     top-level div with no ancestor <td> border never showed this.
     */
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-size: 12px;
        color: #2A1015;
        direction: {{ $isRtl ? 'rtl' : 'ltr' }};
        text-align: {{ $isRtl ? 'right' : 'left' }};
    }

    table { border-collapse: collapse; width: 100%; }
    .ltr { direction: ltr; unicode-bidi: embed; text-align: left; }
    .ltr-center { direction: ltr; unicode-bidi: embed; text-align: center; }
    .muted { color: #9C5064; }

    /* ----- Header ----- */
    .header {
        background-color: #3C0B17;
        color: #ffffff;
        border-radius: 10px;
        padding: 14px 20px;
        page-break-inside: avoid;
    }
    .header td { vertical-align: top; }
    .brand-logo-inline { width: 24px; height: 24px; vertical-align: middle; margin: 0 8px -4px 8px; }
    .brand-mark { font-size: 20px; font-weight: bold; color: #E8C39A; }
    .brand-tagline { font-size: 10px; color: #F7F0E7; margin-top: 4px; }
    .invoice-title { font-size: 15px; font-weight: bold; text-transform: uppercase; color: #ffffff; }
    .invoice-number { font-size: 11px; color: #E8C39A; font-weight: bold; margin-top: 4px; }
    .invoice-date { font-size: 10px; color: #F7F0E7; margin-top: 2px; }

    /* ----- Order info strip ----- */
    .strip {
        margin-top: 8px;
        background-color: #F7F0E7;
        border-radius: 8px;
        page-break-inside: avoid;
    }
    .strip td { padding: 8px 14px; vertical-align: middle; }
    .strip-label { font-size: 9px; text-transform: uppercase; color: #9C5064; font-weight: bold; }
    .strip-value { font-size: 11.5px; font-weight: bold; color: #5B1024; }
    .status-badge {
        display: inline-block;
        padding: 3px 12px;
        border-radius: 10px;
        font-size: 10px;
        font-weight: bold;
        background-color: #5B1024;
        color: #ffffff;
    }

    /* ----- Address cards ----- */
    .cards-row { margin-top: 8px; }
    .card-td {
        background-color: #ffffff;
        border: 1px solid #EAD9C4;
        border-radius: 8px;
        padding: 8px 14px;
        vertical-align: top;
        page-break-inside: avoid;
    }
    .card-label { font-size: 9.5px; font-weight: bold; text-transform: uppercase; color: #5B1024; margin-bottom: 5px; }
    .card-line { font-size: 11px; color: #2A1015; }
    .card-line.strong { font-weight: bold; }
    .card-line.muted { color: #9C5064; font-size: 10px; }

    /* ----- Product table ----- */
    .section-title {
        font-size: 10.5px;
        font-weight: bold;
        text-transform: uppercase;
        color: #5B1024;
        margin: 12px 0 6px;
    }
    .items { border: 1px solid #EAD9C4; }
    .items thead th {
        background-color: #5B1024;
        color: #ffffff;
        font-size: 9px;
        font-weight: bold;
        text-transform: uppercase;
        padding: 7px 8px;
    }
    .items tbody tr { page-break-inside: avoid; }
    .items tbody td {
        padding: 7px 8px;
        border-bottom: 1px solid #EAD9C4;
        font-size: 11px;
        vertical-align: middle;
    }
    .items tbody tr:nth-child(even) td { background-color: #FCFAF6; }
    .item-thumb { width: 32px; height: 32px; border: 1px solid #EAD9C4; }
    .item-name { font-weight: bold; font-size: 11px; }

    /* ----- Totals ----- */
    .totals-wrap { margin-top: 8px; }
    .totals-card-td {
        background-color: #F7F0E7;
        border-radius: 8px;
        padding: 8px 14px;
        vertical-align: top;
        page-break-inside: avoid;
    }
    .totals-row td { padding: 3px 0; font-size: 11px; }
    .totals-row .label { color: #9C5064; }
    .totals-row .value { color: #2A1015; font-weight: bold; }
    .totals-divider { border-top: 1px solid #EAD9C4; margin: 5px 0; }
    .grand-total-label { font-size: 12px; font-weight: bold; color: #5B1024; }
    .grand-total-value { font-size: 15px; font-weight: bold; color: #5B1024; }

    /* ----- Payment / shipping meta ----- */
    .meta-grid { margin-top: 8px; }
    .meta-box-td {
        background-color: #ffffff;
        border: 1px solid #EAD9C4;
        border-radius: 8px;
        padding: 6px 12px;
        vertical-align: top;
        page-break-inside: avoid;
    }
    .meta-box-td .label { font-size: 9px; text-transform: uppercase; color: #9C5064; font-weight: bold; }
    .meta-box-td .value { font-size: 11px; font-weight: bold; color: #2A1015; margin-top: 2px; }

    .notes-box {
        margin-top: 8px;
        background-color: #F7F0E7;
        border-{{ $isRtl ? 'right' : 'left' }}: 3px solid #5B1024;
        padding: 6px 14px;
        font-size: 10.5px;
    }

    /* ----- Footer ----- */
    .footer {
        margin-top: 10px;
        padding-top: 6px;
        border-top: 1px solid #EAD9C4;
        text-align: center;
    }
    .footer-brand { font-size: 11px; font-weight: bold; color: #5B1024; }
    .footer-line { font-size: 9.5px; color: #9C5064; margin-top: 2px; }
</style>
</head>
<body>

    <table class="header">
        <tr>
            <td style="width:50%;">
                {{--
                    The icon is an inline <img> inside the existing text
                    divs, not a nested <table> — a nested table is its own
                    block box with a shrink-to-fit width, so it would not
                    be repositioned by this cell's RTL column reversal the
                    way inline content correctly is.
                --}}
                <div class="brand-mark"><img src="{{ public_path('assets/branding/favicon-192.png') }}" class="brand-logo-inline" alt="">{{ __('Dar El Jamila') }}</div>
                <div class="brand-tagline">{{ __('invoice.tagline') }}</div>
            </td>
            <td style="width:50%;" class="{{ $isRtl ? 'ltr' : '' }}" @if(!$isRtl) style="text-align:right;" @endif>
                <div class="invoice-title">{{ __('invoice.invoice') }}</div>
                <div class="invoice-number">{{ $invoiceNumber }}</div>
                <div class="invoice-date">{{ $order->created_at->translatedFormat('F j, Y') }}</div>
            </td>
        </tr>
    </table>

    <table class="strip">
        <tr>
            <td style="width:34%;">
                <span class="strip-label">{{ __('invoice.order_number') }}</span><br>
                <span class="strip-value ltr" style="direction:ltr; unicode-bidi:embed;">{{ $order->order_number }}</span>
            </td>
            <td style="width:32%; text-align:center;">
                <span class="strip-label">{{ __('invoice.order_status') }}</span><br>
                <span class="status-badge">{{ __('orders.status_'.$order->status) }}</span>
            </td>
            <td style="width:34%;">
                <span class="strip-label">{{ __('invoice.date') }}</span><br>
                <span class="strip-value">{{ $order->created_at->translatedFormat('F j, Y') }}</span>
            </td>
        </tr>
    </table>

    <table class="cards-row">
        <tr>
            <td class="card-td" style="width:49%;">
                <div class="card-label">{{ __('invoice.bill_to') }}</div>
                <div class="card-line strong">{{ $order->customer_name }}</div>
                <div class="card-line muted ltr" style="direction:ltr; unicode-bidi:embed; text-align:{{ $isRtl ? 'right' : 'left' }};">{{ $order->customer_email }}</div>
                <div class="card-line muted ltr" style="direction:ltr; unicode-bidi:embed; text-align:{{ $isRtl ? 'right' : 'left' }};">{{ $order->customer_phone }}</div>
            </td>
            <td class="spacer" style="width:2%;"></td>
            <td class="card-td" style="width:49%;">
                <div class="card-label">{{ __('invoice.ship_to') }}</div>
                <div class="card-line strong">{{ $order->customer_name }}</div>
                <div class="card-line">{{ $order->address }}</div>
                <div class="card-line muted">{{ $order->city }}, {{ $order->governorate }}</div>
            </td>
        </tr>
    </table>

    <div class="section-title">{{ __('invoice.item') }}</div>
    <table class="items">
        <thead>
            <tr>
                <th style="width:38%;">{{ __('invoice.item') }}</th>
                <th>{{ __('invoice.sku') }}</th>
                <th>{{ __('invoice.size') }}</th>
                <th>{{ __('invoice.quantity') }}</th>
                <th>{{ __('invoice.price') }}</th>
                <th>{{ __('invoice.total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($djItems as $item)
                <tr>
                    <td>
                        <table style="width:100%;"><tr>
                            <td style="width:36px; padding:0; border:none;">
                                @if ($item['localImagePath'])
                                    <img src="{{ $item['localImagePath'] }}" class="item-thumb" alt="">
                                @else
                                    <div class="item-thumb" style="background-color:#F1E4D3;">&nbsp;</div>
                                @endif
                            </td>
                            <td style="padding:0 6px; border:none;">
                                <div class="item-name">{{ $item['name'] }}</div>
                            </td>
                        </tr></table>
                    </td>
                    <td class="ltr-center">{{ $item['sku'] }}</td>
                    <td class="ltr-center">{{ $item['size'] }}</td>
                    <td class="ltr-center">{{ $item['quantity'] }}</td>
                    <td class="ltr-center">{{ number_format($item['price']) }} EGP</td>
                    <td class="ltr-center" style="font-weight:bold;">{{ number_format($item['lineTotal']) }} EGP</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-wrap">
        <tr>
            <td style="width:45%;"></td>
            <td class="totals-card-td" style="width:55%;">
                <table class="totals-row">
                    <tr>
                        <td class="label">{{ __('invoice.subtotal') }}</td>
                        <td class="value ltr-center">{{ number_format($order->subtotal) }} EGP</td>
                    </tr>
                    @if ($order->discount_amount > 0)
                        <tr>
                            <td class="label">{{ __('invoice.discount') }}@if($order->coupon_code) ({{ $order->coupon_code }})@endif</td>
                            <td class="value ltr-center">-{{ number_format($order->discount_amount) }} EGP</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="label">{{ __('invoice.shipping') }}</td>
                        <td class="value ltr-center">{{ number_format($order->shipping_fee) }} EGP</td>
                    </tr>
                    <tr><td colspan="2"><div class="totals-divider"></div></td></tr>
                    <tr>
                        <td class="grand-total-label">{{ __('invoice.grand_total') }}</td>
                        <td class="grand-total-value ltr-center">{{ number_format($order->total) }} EGP</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    @php
        $djHasShipping = $order->shipping_method_name || $order->shippingMethod;
    @endphp
    <table class="meta-grid">
        <tr>
            <td class="meta-box-td" style="{{ $djHasShipping ? 'width:49%;' : 'width:100%;' }}">
                <div class="label">{{ __('invoice.payment_method') }}</div>
                <div class="value">{{ $order->payment_method === \App\Models\Order::PAYMENT_METHOD_COD ? __('invoice.payment_method_cod') : $order->payment_method }}</div>
            </td>
            @if ($djHasShipping)
                <td class="spacer" style="width:2%;"></td>
                <td class="meta-box-td" style="width:49%;">
                    <div class="label">{{ __('invoice.shipping') }}</div>
                    <div class="value">{{ $order->shipping_method_name ?? ($isRtl ? $order->shippingMethod->name_ar : $order->shippingMethod->name_en) }}</div>
                </td>
            @endif
        </tr>
    </table>

    @if ($order->notes)
        <div class="notes-box">
            <strong>{{ __('invoice.notes') }}:</strong> {{ $order->notes }}
        </div>
    @endif

    <div class="footer">
        <div class="footer-brand">{{ __('Dar El Jamila') }}</div>
        <div class="footer-line">{{ __('invoice.thank_you') }}</div>
        @if ($djSupportEmail ?? null)
            <div class="footer-line">{{ __('invoice.contact_support', ['email' => $djSupportEmail]) }}</div>
        @endif
        @if ($djWhatsapp ?? null)
            <div class="footer-line ltr-center">{{ $djWhatsapp }}</div>
        @endif
        <div class="footer-line">&copy; {{ now()->year }} {{ __('Dar El Jamila') }}</div>
    </div>

</body>
</html>
