<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('invoice.invoice') }} {{ $invoiceNumber }}</title>
    <style>
        @font-face {
            font-family: 'Cairo';
            font-weight: normal;
            src: url('{{ resource_path('fonts/cairo/Cairo-Regular.ttf') }}') format('truetype');
        }
        @font-face {
            font-family: 'Cairo';
            font-weight: bold;
            src: url('{{ resource_path('fonts/cairo/Cairo-Bold.ttf') }}') format('truetype');
        }
        @font-face {
            font-family: 'Cairo';
            font-weight: 600;
            src: url('{{ resource_path('fonts/cairo/Cairo-SemiBold.ttf') }}') format('truetype');
        }

        * { margin: 0; padding: 0; }
        p { margin: 0 0 4px; }

        body {
            font-family: 'Cairo', 'DejaVu Sans', sans-serif;
            font-size: 12.5px;
            color: #2A1015;
            direction: {{ $isRtl ? 'rtl' : 'ltr' }};
            unicode-bidi: embed;
        }
        table { width: 100%; border-collapse: collapse; }
        .text-start { text-align: {{ $isRtl ? 'right' : 'left' }}; }
        .text-end { text-align: {{ $isRtl ? 'left' : 'right' }}; }
        .muted { color: #9C5064; }

        /* ----- Header ----- */
        .brand-bar { background: #3C0B17; padding: 22px 28px; }
        .brand-name { color: #E8C39A; font-size: 22px; font-weight: bold; margin: 0; }
        .brand-tagline { color: #F7EFE4; font-size: 11px; margin: 3px 0 0; opacity: .85; }
        .invoice-title { color: #F7EFE4; font-size: 18px; font-weight: bold; margin: 0; }
        .invoice-meta { color: #E8C39A; font-size: 11.5px; margin: 3px 0 0; }

        .page { padding: 24px 28px; }

        /* ----- Address blocks ----- */
        .address-box {
            background: #F7EFE4;
            border: 1px solid #EFE2CE;
            border-radius: 6px;
            padding: 12px 14px;
            vertical-align: top;
        }
        .address-label {
            color: #601526;
            font-size: 10.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .4px;
            margin: 0 0 6px;
        }
        .address-line { font-size: 12px; color: #2A1015; line-height: 1.6; }

        /* ----- Items table ----- */
        .items-table { margin-top: 22px; }
        .items-table thead th {
            background: #601526; color: #F7EFE4; font-size: 10.5px; text-transform: uppercase;
            letter-spacing: .3px; padding: 9px 10px; font-weight: bold;
        }
        .items-table tbody td {
            padding: 9px 10px; border-bottom: 1px solid #EFE2CE; font-size: 12px; vertical-align: top;
        }
        .item-name { font-weight: bold; color: #2A1015; }
        .item-variant { color: #9C5064; font-size: 10.5px; margin-top: 2px; }

        /* ----- Totals ----- */
        .totals-table { width: 260px; margin-top: 14px; }
        .totals-table.pull-end { margin-{{ $isRtl ? 'right' : 'left' }}: auto; }
        .totals-table td { padding: 5px 10px; font-size: 12px; }
        .totals-table .grand-total td {
            border-top: 2px solid #601526; font-weight: bold; font-size: 15px; color: #601526; padding-top: 10px;
        }

        /* ----- Meta table (payment/status/notes) ----- */
        .meta-table { margin-top: 22px; }
        .meta-table td { padding: 5px 0; font-size: 12px; vertical-align: top; }
        .meta-label { color: #9C5064; width: 150px; }
        .status-badge {
            display: inline-block; background: #E8C39A; color: #601526; font-size: 10.5px;
            font-weight: bold; padding: 3px 10px; border-radius: 10px;
        }

        /* ----- Footer ----- */
        .footer {
            margin-top: 30px; padding-top: 14px; border-top: 1px solid #EFE2CE;
            text-align: center; color: #9C5064; font-size: 10.5px; line-height: 1.7;
        }
    </style>
</head>
<body>
    <div class="brand-bar">
        <p class="brand-name">{{ __('Dar El-Jamila') }}</p>
        <p class="brand-tagline">{{ __('invoice.tagline') }}</p>

        <div style="height:14px;"></div>

        <p class="invoice-title">{{ __('invoice.invoice') }} — {{ $invoiceNumber }}</p>
        <p class="invoice-meta">{{ __('invoice.order_number') }}: {{ $order->order_number }}</p>
        <p class="invoice-meta">{{ __('invoice.date') }}: {{ $order->created_at->translatedFormat('F j, Y') }}</p>
    </div>

    <div class="page">
        <table>
            <tr>
                <td style="width:50%; padding-{{ $isRtl ? 'left' : 'right' }}:8px;" class="address-box">
                    <p class="address-label">{{ __('invoice.bill_to') }}</p>
                    <p class="address-line"><strong>{{ $order->customer_name }}</strong></p>
                    <p class="address-line">{{ $order->customer_email }}</p>
                    <p class="address-line">{{ $order->customer_phone }}</p>
                </td>
                <td style="width:50%; padding-{{ $isRtl ? 'right' : 'left' }}:8px;" class="address-box">
                    <p class="address-label">{{ __('invoice.ship_to') }}</p>
                    <p class="address-line">{{ $order->address }}</p>
                    <p class="address-line">{{ $order->city }}, {{ $order->governorate }}</p>
                </td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th class="text-start">{{ __('invoice.item') }}</th>
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
                    @endphp
                    <tr>
                        <td class="text-start">
                            <div class="item-name">{{ $djItemName }}</div>
                        </td>
                        <td class="text-start">{{ $item->product?->sku ?? '-' }}</td>
                        <td class="text-start">{{ $item->size ?? '-' }}</td>
                        <td class="text-end">{{ $item->quantity }}</td>
                        <td class="text-end">{{ number_format($item->price) }} EGP</td>
                        <td class="text-end">{{ number_format($item->price * $item->quantity) }} EGP</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="totals-table pull-end">
            <tr>
                <td class="text-start muted">{{ __('invoice.subtotal') }}</td>
                <td class="text-end">{{ number_format($order->subtotal) }} EGP</td>
            </tr>
            @if ($order->discount_amount > 0)
                <tr>
                    <td class="text-start muted">{{ __('invoice.discount') }}@if($order->coupon_code) ({{ $order->coupon_code }})@endif</td>
                    <td class="text-end">-{{ number_format($order->discount_amount) }} EGP</td>
                </tr>
            @endif
            <tr>
                <td class="text-start muted">{{ __('invoice.shipping') }}</td>
                <td class="text-end">{{ number_format($order->shipping_fee) }} EGP</td>
            </tr>
            <tr class="grand-total">
                <td class="text-start">{{ __('invoice.grand_total') }}</td>
                <td class="text-end">{{ number_format($order->total) }} EGP</td>
            </tr>
        </table>

        <table class="meta-table">
            <tr>
                <td class="meta-label">{{ __('invoice.payment_method') }}</td>
                <td>{{ $order->payment_method === 'cod' ? __('invoice.payment_method_cod') : $order->payment_method }}</td>
            </tr>
            <tr>
                <td class="meta-label">{{ __('invoice.order_status') }}</td>
                <td><span class="status-badge">{{ __('orders.status_'.$order->status) }}</span></td>
            </tr>
            @if ($order->notes)
                <tr>
                    <td class="meta-label">{{ __('invoice.notes') }}</td>
                    <td>{{ $order->notes }}</td>
                </tr>
            @endif
        </table>

        <div class="footer">
            <p>{{ __('invoice.thank_you') }}</p>
            @if ($djSupportEmail ?? null)
                <p>{{ __('invoice.contact_support', ['email' => $djSupportEmail]) }}</p>
            @endif
        </div>
    </div>
</body>
</html>
