@extends('admin.layout')

@section('title', $order->order_number)

@section('content')
    <div class="flex justify-between items-center mb-6">
        <a href="{{ route('admin.orders.index') }}" class="dj-admin-link">&larr; {{ __('orders.all_orders') }}</a>
        <a href="{{ route('admin.orders.invoice', $order) }}" class="dj-admin-btn dj-admin-btn-secondary">{{ __('orders.download_invoice') }}</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="md:col-span-2 space-y-6">
            <div class="dj-admin-card">
                @foreach ($order->items as $item)
                    <div class="p-4 flex justify-between text-sm border-t border-[var(--dj-cream-2)] first:border-t-0">
                        <span>{{ $item->product_name }} ({{ $item->size ?? '-' }}) &times; {{ $item->quantity }}</span>
                        <span class="font-semibold text-[var(--dj-maroon)]">{{ number_format($item->price * $item->quantity) }} EGP</span>
                    </div>
                @endforeach
            </div>

            <div class="dj-admin-card p-4">
                <h2 class="font-semibold mb-3 text-[var(--dj-maroon-dark)]">{{ __('orders.push_new_status') }}</h2>
                <form method="POST" action="{{ route('admin.orders.status', $order) }}" class="flex flex-col sm:flex-row gap-3 sm:items-start">
                    @csrf
                    @method('PATCH')
                    <select name="status" class="dj-admin-input w-full sm:w-auto">
                        @foreach (['pending', 'processing', 'shipped', 'delivered', 'cancelled'] as $status)
                            <option value="{{ $status }}" @selected($order->status === $status)>{{ __('orders.status_'.$status) }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="note" placeholder="{{ __('orders.note_optional') }}" class="dj-admin-input flex-1 w-full">
                    <button class="dj-admin-btn dj-admin-btn-primary shrink-0">{{ __('general.update') }}</button>
                </form>
            </div>

            <div>
                <h2 class="font-semibold mb-3 text-[var(--dj-maroon-dark)]">{{ __('orders.status_history') }}</h2>
                <ul class="space-y-2 text-sm">
                    @foreach ($order->statusHistories as $history)
                        <li class="flex justify-between border-b border-[var(--dj-cream-2)] pb-2">
                            <span>{{ __('orders.status_'.$history->status) }} @if($history->note) — {{ $history->note }} @endif @if($history->changedBy) <span class="text-[var(--dj-rose-dust)]">{{ __('orders.by') }} {{ $history->changedBy->name }}</span> @endif</span>
                            <span class="text-[var(--dj-rose-dust)]">{{ $history->created_at->format('M j, Y H:i') }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="space-y-4 text-sm">
            <div class="dj-admin-card p-4">
                <h2 class="font-semibold mb-2 text-[var(--dj-maroon-dark)]">{{ __('orders.customer') }}</h2>
                <p>{{ $order->customer_name }}</p>
                <p>{{ $order->customer_email }}</p>
                <p>{{ $order->customer_phone }}</p>
            </div>
            <div class="dj-admin-card p-4">
                <h2 class="font-semibold mb-2 text-[var(--dj-maroon-dark)]">{{ __('orders.shipping_address') }}</h2>
                <p>{{ $order->address }}</p>
                <p>{{ $order->city }}, {{ $order->governorate }}</p>
                <p class="mt-2 text-[var(--dj-rose-dust)]">{{ $order->shippingMethod?->name_en }}</p>
            </div>
            <div class="dj-admin-card p-4 space-y-1">
                <div class="flex justify-between"><span>{{ __('orders.subtotal') }}</span><span>{{ number_format($order->subtotal) }} EGP</span></div>
                <div class="flex justify-between"><span>{{ __('orders.shipping') }}</span><span>{{ number_format($order->shipping_fee) }} EGP</span></div>
                @if ($order->discount_amount > 0)
                    <div class="flex justify-between text-[#2f7a4d]"><span>{{ __('orders.discount') }} ({{ $order->coupon_code }})</span><span>-{{ number_format($order->discount_amount) }} EGP</span></div>
                @endif
                <div class="flex justify-between font-semibold pt-2 border-t border-[var(--dj-cream-2)] text-[var(--dj-maroon-dark)]"><span>{{ __('orders.total') }}</span><span>{{ number_format($order->total) }} EGP</span></div>
            </div>
        </div>
    </div>
@endsection
