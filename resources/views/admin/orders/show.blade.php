@extends('admin.layout')

@section('title', $order->order_number)

@section('content')
    <div class="flex justify-between items-center mb-6">
        <a href="{{ route('admin.orders.index') }}" class="text-sm text-rose-700 underline">&larr; All Orders</a>
        <a href="{{ route('admin.orders.invoice', $order) }}" class="bg-stone-800 text-white text-sm px-4 py-2 rounded">Download Invoice</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="md:col-span-2 space-y-6">
            <div class="bg-white border border-stone-200 rounded-lg divide-y divide-stone-100">
                @foreach ($order->items as $item)
                    <div class="p-4 flex justify-between text-sm">
                        <span>{{ $item->product_name }} ({{ $item->size ?? '-' }}) &times; {{ $item->quantity }}</span>
                        <span>{{ number_format($item->price * $item->quantity) }} EGP</span>
                    </div>
                @endforeach
            </div>

            <div class="bg-white border border-stone-200 rounded-lg p-4">
                <h2 class="font-medium mb-3">Push New Status</h2>
                <form method="POST" action="{{ route('admin.orders.status', $order) }}" class="flex flex-col sm:flex-row gap-3 sm:items-start">
                    @csrf
                    @method('PATCH')
                    <select name="status" class="w-full sm:w-auto rounded border-stone-300 text-sm">
                        @foreach (['pending', 'processing', 'shipped', 'delivered', 'cancelled'] as $status)
                            <option value="{{ $status }}" @selected($order->status === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="note" placeholder="Note (optional)" class="flex-1 w-full rounded border-stone-300 text-sm">
                    <button class="bg-rose-700 hover:bg-rose-800 text-white text-sm px-4 py-2 rounded">Update</button>
                </form>
            </div>

            <div>
                <h2 class="font-medium mb-3">Status History</h2>
                <ul class="space-y-2 text-sm">
                    @foreach ($order->statusHistories as $history)
                        <li class="flex justify-between border-b border-stone-100 pb-2">
                            <span>{{ ucfirst($history->status) }} @if($history->note) — {{ $history->note }} @endif @if($history->changedBy) <span class="text-stone-400">by {{ $history->changedBy->name }}</span> @endif</span>
                            <span class="text-stone-400">{{ $history->created_at->format('M j, Y H:i') }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="space-y-4 text-sm">
            <div class="bg-white border border-stone-200 rounded-lg p-4">
                <h2 class="font-medium mb-2">Customer</h2>
                <p>{{ $order->customer_name }}</p>
                <p>{{ $order->customer_email }}</p>
                <p>{{ $order->customer_phone }}</p>
            </div>
            <div class="bg-white border border-stone-200 rounded-lg p-4">
                <h2 class="font-medium mb-2">Shipping Address</h2>
                <p>{{ $order->address }}</p>
                <p>{{ $order->city }}, {{ $order->governorate }}</p>
                <p class="mt-2 text-stone-500">{{ $order->shippingMethod?->name_en }}</p>
            </div>
            <div class="bg-white border border-stone-200 rounded-lg p-4 space-y-1">
                <div class="flex justify-between"><span>Subtotal</span><span>{{ number_format($order->subtotal) }} EGP</span></div>
                <div class="flex justify-between"><span>Shipping</span><span>{{ number_format($order->shipping_fee) }} EGP</span></div>
                @if ($order->discount_amount > 0)
                    <div class="flex justify-between text-green-700"><span>Discount ({{ $order->coupon_code }})</span><span>-{{ number_format($order->discount_amount) }} EGP</span></div>
                @endif
                <div class="flex justify-between font-semibold pt-2 border-t border-stone-200"><span>Total</span><span>{{ number_format($order->total) }} EGP</span></div>
            </div>
        </div>
    </div>
@endsection
