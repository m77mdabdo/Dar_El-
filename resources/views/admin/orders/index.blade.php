@extends('admin.layout')

@section('title', 'Orders')

@section('content')
    <form method="GET" class="flex flex-col sm:flex-row gap-3 mb-4">
        <select name="status" onchange="this.form.submit()" class="w-full sm:w-auto rounded border-stone-300 text-sm">
            <option value="">All Statuses</option>
            @foreach (['pending', 'processing', 'shipped', 'delivered', 'cancelled'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        <select name="payment_status" onchange="this.form.submit()" class="w-full sm:w-auto rounded border-stone-300 text-sm">
            <option value="">All Payment Statuses</option>
            @foreach (['pending', 'paid', 'failed', 'refunded'] as $status)
                <option value="{{ $status }}" @selected(request('payment_status') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
    </form>

    <div class="bg-white border border-stone-200 rounded-lg overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-stone-50 text-left text-xs uppercase text-stone-500">
                <tr>
                    <th class="px-4 py-3">Order #</th>
                    <th class="px-4 py-3">Customer</th>
                    <th class="px-4 py-3">Total</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Payment</th>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @foreach ($orders as $order)
                    <tr>
                        <td class="px-4 py-3">{{ $order->order_number }}</td>
                        <td class="px-4 py-3">{{ $order->customer_name }}</td>
                        <td class="px-4 py-3">{{ number_format($order->total) }} EGP</td>
                        <td class="px-4 py-3">{{ ucfirst($order->status) }}</td>
                        <td class="px-4 py-3">{{ $order->payment ? ucfirst($order->payment->status) : '-' }}</td>
                        <td class="px-4 py-3">{{ $order->created_at->format('M j, Y') }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.orders.show', $order) }}" class="text-rose-700 underline">View</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $orders->links() }}</div>
@endsection
