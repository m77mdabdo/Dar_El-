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
        font-size: 12px;
        color: var(--dj-ink);
        background: #ffffff;
        direction: {{ $isRtl ? 'rtl' : 'ltr' }};
        unicode-bidi: embed;
        -webkit-font-smoothing: antialiased;
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }

    table { border-collapse: collapse; }
    .text-start { text-align: {{ $isRtl ? 'right' : 'left' }}; }
    .text-end { text-align: {{ $isRtl ? 'left' : 'right' }}; }
    .muted { color: var(--dj-muted); }

    /*
     * Everything below is deliberately table-based rather than flexbox.
     * dompdf's flexbox support is unreliable in practice — verified by
     * rendering this exact template: a flex .header collapsed its two
     * children onto separate lines instead of a single row, and a flex
     * .meta-grid stacked its two boxes vertically instead of side by side,
     * while other flex blocks on the same page happened to render fine.
     * Since the failure is inconsistent rather than total, it's a trap —
     * a template can look correct in one test render and break in the
     * next. Plain HTML tables are dompdf's one genuinely reliable layout
     * primitive, so every multi-column block here uses one.
     *
     * A second, more specific dompdf bug was found and confirmed by
     * isolating it in a minimal test file: a multi-column <table> inside
     * an RTL-direction document does NOT reverse column order the way a
     * browser does — instead, each column shrinks to its content width
     * and the whole row gets centered as a single unit, which is exactly
     * what caused the header's brand block and invoice-info block to
     * visually overlap/collide. Forcing dir="ltr" directly on each
     * multi-column <table> (while the surrounding page stays RTL for all
     * prose/labels) restores normal, reliable column layout; the correct
     * *visual* RTL order is then produced by swapping which content goes
     * in the first vs. second markup cell — the first cell always ends
     * up physically on the right when the table is forced ltr, so for an
     * RTL invoice the content that should read first (start-aligned)
     * goes there instead.
     *
     * Page padding and all vertical rhythm (card padding, row padding,
     * section margins) are intentionally tight: a top margin as small as
     * 26mm on a 297mm page, plus generous 1.75 line-heights and 18-28px
     * gaps between every block, was enough on its own to push a normal
     * 1-2 item order onto a second page with nothing but the totals box
     * on it. None of that spacing was load-bearing for readability.
     */

    .page { padding: 6mm 14mm 6mm; }

    /* ----- Header ----- */
    .header {
        /*
         * Solid color only — confirmed by isolated testing that dompdf's
         * `background: linear-gradient(...)` shorthand, when paired with
         * a preceding `background-color` fallback (the technique used
         * successfully in the HTML email templates, which render in real
         * browsers/email clients, not dompdf), causes dompdf to drop the
         * background entirely rather than falling back to the solid
         * color. Confirmed with get_drawings() on the rendered PDF: zero
         * fill rectangles with the gradient line present, one correct
         * fill rectangle with only background-color set. This is a
         * dompdf-specific limitation, not something browsers hit.
         */
        background-color: #3C0B17;
        color: #fff;
        border-radius: 14px;
        padding: 14px 24px;
        page-break-inside: avoid;
    }
    .header table td { vertical-align: top; }
    .brand-logo-inline { width: 22px; height: 22px; vertical-align: middle; margin: 0 8px -4px 8px; }
    .brand-mark { font-size: 22px; font-weight: 700; letter-spacing: .5px; color: var(--dj-gold); }
    .brand-tagline { font-size: 10.5px; color: #F7F0E7; margin-top: 4px; letter-spacing: .3px; }
    .invoice-title { font-size: 17px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: #fff; }
    .invoice-number { font-size: 12px; color: var(--dj-gold); font-weight: 600; margin-top: 4px; }
    .invoice-date { font-size: 10.5px; color: #F7F0E7; margin-top: 2px; }

    /* ----- Status + order number strip ----- */
    .strip {
        width: 100%;
        margin-top: 10px;
        background: var(--dj-secondary);
        border-radius: 10px;
        page-break-inside: avoid;
    }
    .strip td { padding: 9px 16px; vertical-align: middle; }
    .strip-label { font-size: 9.5px; text-transform: uppercase; letter-spacing: .6px; color: var(--dj-muted); font-weight: 600; display: block; }
    .strip-value { font-size: 12.5px; font-weight: 700; color: var(--dj-primary); }
    .status-badge {
        display: inline-block;
        padding: 4px 14px;
        border-radius: 999px;
        font-size: 10.5px;
        font-weight: 700;
        letter-spacing: .3px;
        background: var(--dj-primary);
        color: #fff;
    }

    /* ----- Address cards ----- */
    .cards-row { width: 100%; margin-top: 8px; }
    .cards-row td { vertical-align: top; width: 50%; }
    .cards-row .spacer { width: 12px; }
    .card {
        background: #fff;
        border: 1px solid var(--dj-border);
        border-radius: 12px;
        padding: 10px 16px;
        page-break-inside: avoid;
    }
    .card-label {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: var(--dj-primary);
        margin-bottom: 6px;
    }
    .card-label .dot { width: 5px; height: 5px; border-radius: 50%; background: var(--dj-gold); display: inline-block; margin-{{ $isRtl ? 'left' : 'right' }}: 5px; }
    .card-line { font-size: 11.5px; line-height: 1.5; color: var(--dj-ink); }
    .card-line.strong { font-weight: 700; }
    .card-line.muted { color: var(--dj-muted); font-size: 10.5px; }

    /* ----- Product table ----- */
    .section-title {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: var(--dj-primary);
        margin: 14px 0 6px;
    }
    /*
     * No border-radius/overflow:hidden on this table: dompdf's table
     * pagination breaks the rounded-corner clipping when a table splits
     * across pages, which is exactly what previously happened here —
     * the header row's background vanished into a blank gap on the
     * second page. A plain rectangular border is the reliable option,
     * and dompdf repeats <thead> on every page a table spans, so a
     * multi-page order still gets a header row on each page.
     */
    .items {
        width: 100%;
        border: 1px solid var(--dj-border);
    }
    .items thead th {
        background: var(--dj-primary);
        color: #fff;
        font-size: 9.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .4px;
        padding: 8px 10px;
    }
    .items tbody tr { page-break-inside: avoid; }
    .items tbody td {
        padding: 8px 10px;
        border-bottom: 1px solid var(--dj-border);
        font-size: 11.5px;
        vertical-align: middle;
        background: #fff;
    }
    .items tbody tr:last-child td { border-bottom: none; }
    .items tbody tr:nth-child(even) td { background: #FCFAF6; }
    .item-thumb {
        width: 36px; height: 36px; border-radius: 6px; object-fit: cover;
        border: 1px solid var(--dj-border); vertical-align: middle;
    }
    .item-name { font-weight: 700; color: var(--dj-ink); font-size: 11.5px; }

    /* ----- Totals ----- */
    .totals-wrap { text-align: {{ $isRtl ? 'left' : 'right' }}; margin-top: 8px; }
    .totals-card {
        display: inline-block;
        width: 260px;
        background: var(--dj-secondary);
        border-radius: 12px;
        padding: 10px 16px;
        text-align: {{ $isRtl ? 'right' : 'left' }};
        page-break-inside: avoid;
    }
    .totals-row { width: 100%; font-size: 11.5px; }
    .totals-row td { padding: 3px 0; }
    .totals-row .label { color: var(--dj-muted); }
    .totals-row .value { color: var(--dj-ink); font-weight: 600; }
    .totals-divider { height: 1px; background: var(--dj-border); margin: 6px 0; }
    .grand-total-row { width: 100%; }
    .grand-total-label { font-size: 12.5px; font-weight: 700; color: var(--dj-primary); }
    .grand-total-value { font-size: 17px; font-weight: 700; color: var(--dj-primary); }

    /* ----- Meta grid (payment/status) ----- */
    .meta-grid { width: 100%; margin-top: 8px; }
    .meta-grid td { vertical-align: top; width: 50%; }
    .meta-grid .spacer { width: 12px; }
    .meta-box {
        background: #fff;
        border: 1px solid var(--dj-border);
        border-radius: 12px;
        padding: 8px 14px;
        page-break-inside: avoid;
    }
    .meta-box .label { font-size: 9.5px; text-transform: uppercase; letter-spacing: .5px; color: var(--dj-muted); font-weight: 700; }
    .meta-box .value { font-size: 12px; font-weight: 700; color: var(--dj-ink); margin-top: 3px; }

    .notes-box {
        margin-top: 8px;
        background: var(--dj-secondary);
        border-{{ $isRtl ? 'right' : 'left' }}: 3px solid var(--dj-primary);
        padding: 8px 16px;
        font-size: 11px;
        color: var(--dj-ink);
        line-height: 1.4;
    }

    /* ----- Footer ----- */
    .footer {
        margin-top: 10px;
        padding-top: 6px;
        border-top: 1px solid var(--dj-border);
        text-align: center;
    }
    .footer-brand { font-size: 12px; font-weight: 700; color: var(--dj-primary); }
    .footer-line { font-size: 10px; color: var(--dj-muted); margin-top: 2px; line-height: 1.35; }
</style>
</head>
<body>
<div class="page">

    @php
        // See the CSS comment above .page for why: dompdf does not
        // reverse <table> column order for RTL, so the table itself is
        // always forced to ltr and these arrays put the "reads first"
        // content in the array's first slot — rendered into whichever
        // markup cell ends up physically on the correct side.
        // A plain inline <img> inside the existing block-level divs, not a
        // nested <table> — a nested table is a block box with its own
        // shrink-to-fit width, so the parent td's text-align:right (used
        // for the Arabic/RTL slot) would not reposition it, leaving the
        // logo stranded on the physical left even when this block renders
        // in the right-hand cell. An inline <img> is positioned by the
        // same inherited text-align as the surrounding text, exactly like
        // the .brand-mark/.brand-tagline divs already were.
        $djBrandLogoTag = '<img src="'.e(public_path('assets/branding/favicon-192.png')).'" class="brand-logo-inline" alt="">';
        $djBrandBlock = '<div class="brand-mark">'.$djBrandLogoTag.e(__('Dar El Jamila')).'</div><div class="brand-tagline">'.e(__('invoice.tagline')).'</div>';
        $djInvoiceBlock = '<div class="invoice-title">'.e(__('invoice.invoice')).'</div><div class="invoice-number">'.e($invoiceNumber).'</div><div class="invoice-date">'.e($order->created_at->translatedFormat('F j, Y')).'</div>';
        $djHeaderCells = $isRtl ? [$djInvoiceBlock, $djBrandBlock] : [$djBrandBlock, $djInvoiceBlock];
    @endphp
    <div class="header">
        <table dir="ltr" style="width:100%; direction:ltr;">
            <tr>
                <td style="width:50%; text-align:left; vertical-align:top;">{!! $djHeaderCells[0] !!}</td>
                <td style="width:50%; text-align:right; vertical-align:top;">{!! $djHeaderCells[1] !!}</td>
            </tr>
        </table>
    </div>

    @php
        $djOrderNumberCell = '<span class="strip-label">'.e(__('invoice.order_number')).'</span><span class="strip-value">'.e($order->order_number).'</span>';
        $djDateCell = '<span class="strip-label">'.e(__('invoice.date')).'</span><span class="strip-value">'.e($order->created_at->translatedFormat('F j, Y')).'</span>';
        $djStripSides = $isRtl ? [$djDateCell, $djOrderNumberCell] : [$djOrderNumberCell, $djDateCell];
    @endphp
    <table class="strip" dir="ltr" style="direction:ltr;">
        <tr>
            <td style="width:34%; text-align:left;">{!! $djStripSides[0] !!}</td>
            <td style="width:32%; text-align:center;">
                <span class="strip-label">{{ __('invoice.order_status') }}</span>
                <span class="status-badge">{{ __('orders.status_'.$order->status) }}</span>
            </td>
            <td style="width:34%; text-align:right;">{!! $djStripSides[1] !!}</td>
        </tr>
    </table>

    @php
        $djBillToCard = '<div class="card"><div class="card-label"><span class="dot"></span>'.e(__('invoice.bill_to')).'</div><div class="card-line strong">'.e($order->customer_name).'</div><div class="card-line muted">'.e($order->customer_email).'</div><div class="card-line muted">'.e($order->customer_phone).'</div></div>';
        $djShipToCard = '<div class="card"><div class="card-label"><span class="dot"></span>'.e(__('invoice.ship_to')).'</div><div class="card-line strong">'.e($order->customer_name).'</div><div class="card-line">'.e($order->address).'</div><div class="card-line muted">'.e($order->city).', '.e($order->governorate).'</div></div>';
        $djCardSides = $isRtl ? [$djShipToCard, $djBillToCard] : [$djBillToCard, $djShipToCard];
    @endphp
    <table class="cards-row" dir="ltr" style="direction:ltr;">
        <tr>
            <td>{!! $djCardSides[0] !!}</td>
            <td class="spacer"></td>
            <td>{!! $djCardSides[1] !!}</td>
        </tr>
    </table>

    <div class="section-title">{{ __('invoice.item') }}</div>
    {{--
        dir="ltr" here too, for the same reliability reason as the blocks
        above — column order is kept exactly as originally designed
        (image+name, sku, size, qty, price, total) since that left-to-right
        structural order for a numbers-heavy data table is standard even
        in Arabic invoices; text-start/text-end still resolve per $isRtl
        so text within each cell aligns correctly either way.
    --}}
    <table class="items" dir="ltr" style="direction:ltr;">
        <thead>
            <tr>
                <th class="text-start" style="width:40%;">{{ __('invoice.item') }}</th>
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
                        <table dir="ltr" style="width:100%; direction:ltr;"><tr>
                            <td style="width:44px; padding:0;">
                                @if ($djItemImage)
                                    <img src="{{ $djItemImage }}" class="item-thumb" alt="">
                                @else
                                    <div class="item-thumb" style="background:#F1E4D3;"></div>
                                @endif
                            </td>
                            <td style="padding:0 0 0 8px;">
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

    @php
        // Same left/right-forcing technique as above, applied per row:
        // label+value swap sides so the label always ends up on the
        // "start" side (right for RTL, left for LTR) and the value on
        // the "end" side, regardless of dompdf's RTL table limitation.
        $djTotalsRows = [
            [__('invoice.subtotal'), number_format($order->subtotal).' EGP'],
        ];
        if ($order->discount_amount > 0) {
            $djTotalsRows[] = [
                __('invoice.discount').($order->coupon_code ? ' ('.$order->coupon_code.')' : ''),
                '-'.number_format($order->discount_amount).' EGP',
            ];
        }
        $djTotalsRows[] = [__('invoice.shipping'), number_format($order->shipping_fee).' EGP'];
    @endphp
    <div class="totals-wrap">
        <div class="totals-card">
            <table class="totals-row" dir="ltr" style="direction:ltr;">
                @foreach ($djTotalsRows as [$djLabel, $djValue])
                    <tr>
                        @if ($isRtl)
                            <td class="value" style="text-align:left;">{{ $djValue }}</td>
                            <td class="label" style="text-align:right;">{{ $djLabel }}</td>
                        @else
                            <td class="label" style="text-align:left;">{{ $djLabel }}</td>
                            <td class="value" style="text-align:right;">{{ $djValue }}</td>
                        @endif
                    </tr>
                @endforeach
            </table>
            <div class="totals-divider"></div>
            <table class="grand-total-row" dir="ltr" style="direction:ltr;">
                <tr>
                    @if ($isRtl)
                        <td class="grand-total-value" style="text-align:left;">{{ number_format($order->total) }} EGP</td>
                        <td class="grand-total-label" style="text-align:right;">{{ __('invoice.grand_total') }}</td>
                    @else
                        <td class="grand-total-label" style="text-align:left;">{{ __('invoice.grand_total') }}</td>
                        <td class="grand-total-value" style="text-align:right;">{{ number_format($order->total) }} EGP</td>
                    @endif
                </tr>
            </table>
        </div>
    </div>

    @php
        $djPaymentBox = '<div class="meta-box"><div class="label">'.e(__('invoice.payment_method')).'</div><div class="value">'.e($order->payment_method === \App\Models\Order::PAYMENT_METHOD_COD ? __('invoice.payment_method_cod') : $order->payment_method).'</div></div>';
        $djHasShipping = $order->shipping_method_name || $order->shippingMethod;
        $djShippingBox = $djHasShipping
            ? '<div class="meta-box"><div class="label">'.e(__('invoice.shipping')).'</div><div class="value">'.e($order->shipping_method_name ?? ($isRtl ? $order->shippingMethod->name_ar : $order->shippingMethod->name_en)).'</div></div>'
            : null;
        // Payment always shows; shipping only when known. When both are
        // present, RTL puts payment (the "reads first" field) on the
        // right — same left/right-forcing technique as the blocks above.
        $djMetaSides = $isRtl ? [$djShippingBox, $djPaymentBox] : [$djPaymentBox, $djShippingBox];
    @endphp
    <table class="meta-grid" dir="ltr" style="direction:ltr;">
        <tr>
            @if ($djHasShipping)
                <td>{!! $djMetaSides[0] !!}</td>
                <td class="spacer"></td>
                <td>{!! $djMetaSides[1] !!}</td>
            @else
                <td>{!! $djPaymentBox !!}</td>
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
            <div class="footer-line" dir="ltr">{{ $djWhatsapp }}</div>
        @endif
        <div class="footer-line">&copy; {{ now()->year }} {{ __('Dar El Jamila') }}</div>
    </div>

</div>
</body>
</html>
