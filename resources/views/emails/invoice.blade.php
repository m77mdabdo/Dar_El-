<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: sans-serif; color: #222; max-width: 600px; margin: 0 auto;">
    <h1 style="font-size: 20px;">Thank you for your order, {{ $order->customer_name }}!</h1>

    <p>Your order <strong>{{ $order->order_number }}</strong> has been placed and your invoice <strong>#{{ $invoice->invoice_number }}</strong> is attached to this email.</p>

    <table style="width: 100%; border-collapse: collapse; margin: 16px 0;">
        <thead>
            <tr style="text-align: left; border-bottom: 1px solid #ddd;">
                <th style="padding: 8px 0;">Item</th>
                <th style="padding: 8px 0; text-align: center;">Qty</th>
                <th style="padding: 8px 0; text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 8px 0;">{{ $item->product_name }} ({{ $item->size ?? '-' }})</td>
                    <td style="padding: 8px 0; text-align: center;">{{ $item->quantity }}</td>
                    <td style="padding: 8px 0; text-align: right;">{{ number_format($item->price * $item->quantity) }} EGP</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p style="font-weight: bold; font-size: 16px;">Total: {{ number_format($order->total) }} EGP</p>

    <p style="margin-top: 24px;">Thanks,<br>{{ config('app.name') }}</p>
</body>
</html>
