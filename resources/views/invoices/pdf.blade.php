<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoiceNumber }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 13px; color: #222; }
        .header { display: flex; justify-content: space-between; margin-bottom: 30px; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        .muted { color: #777; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 8px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background: #f7f2ec; }
        .totals td { border: none; padding: 4px 8px; }
        .totals { width: 300px; margin-left: auto; margin-top: 10px; }
        .grand-total { font-weight: bold; font-size: 15px; border-top: 2px solid #333; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>Dar El-Jamila</h1>
            <div class="muted">Modest Fashion Boutique</div>
        </div>
        <div>
            <h1>Invoice #{{ $invoiceNumber }}</h1>
            <div class="muted">Order {{ $order->order_number }}</div>
            <div class="muted">{{ $order->created_at->format('F j, Y') }}</div>
        </div>
    </div>

    <table style="border: none;">
        <tr style="border: none;">
            <td style="border: none; width: 50%; vertical-align: top;">
                <strong>Bill To</strong><br>
                {{ $order->customer_name }}<br>
                {{ $order->customer_email }}<br>
                {{ $order->customer_phone }}
            </td>
            <td style="border: none; width: 50%; vertical-align: top;">
                <strong>Ship To</strong><br>
                {{ $order->address }}<br>
                {{ $order->city }}, {{ $order->governorate }}
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Size</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>{{ $item->product_name }}</td>
                    <td>{{ $item->size ?? '-' }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format($item->price) }} EGP</td>
                    <td>{{ number_format($item->price * $item->quantity) }} EGP</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td style="text-align: right;">{{ number_format($order->subtotal) }} EGP</td></tr>
        <tr><td>Shipping</td><td style="text-align: right;">{{ number_format($order->shipping_fee) }} EGP</td></tr>
        @if ($order->discount_amount > 0)
            <tr><td>Discount ({{ $order->coupon_code }})</td><td style="text-align: right;">-{{ number_format($order->discount_amount) }} EGP</td></tr>
        @endif
        <tr class="grand-total"><td>Total</td><td style="text-align: right;">{{ number_format($order->total) }} EGP</td></tr>
    </table>

    <p class="muted" style="margin-top: 40px;">Payment method: {{ $order->payment?->gateway ?? 'Cash on Delivery' }}</p>
</body>
</html>
