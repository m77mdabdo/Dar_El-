@extends('admin.layout')

@section('title', __('orders.title'))

@section('content')
    <form method="GET" class="flex flex-col sm:flex-row gap-3 mb-4">
        <select name="status" onchange="this.form.submit()" class="dj-admin-input w-full sm:w-auto">
            <option value="">{{ __('orders.all_statuses') }}</option>
            @foreach (['pending', 'processing', 'shipped', 'delivered', 'cancelled'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ __('orders.status_'.$status) }}</option>
            @endforeach
        </select>
        <select name="payment_status" onchange="this.form.submit()" class="dj-admin-input w-full sm:w-auto">
            <option value="">{{ __('orders.all_payment_statuses') }}</option>
            @foreach (['pending', 'paid', 'failed', 'refunded'] as $status)
                <option value="{{ $status }}" @selected(request('payment_status') === $status)>{{ __('orders.payment_status_'.$status) }}</option>
            @endforeach
        </select>
    </form>

    <div class="dj-admin-card dj-admin-table-wrap">
        <table class="dj-admin-table">
            <thead>
                <tr>
                    <th>{{ __('orders.order_number') }}</th>
                    <th>{{ __('orders.customer') }}</th>
                    <th>{{ __('orders.total') }}</th>
                    <th>{{ __('general.status') }}</th>
                    <th>{{ __('orders.payment') }}</th>
                    <th>{{ __('general.date') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    @php
                        $djOrderBadge = match ($order->status) {
                            'delivered' => 'dj-admin-badge-success',
                            'cancelled' => 'dj-admin-badge-danger',
                            'pending' => 'dj-admin-badge-gold',
                            default => 'dj-admin-badge-info',
                        };
                    @endphp
                    <tr>
                        <td class="font-semibold text-[var(--dj-maroon)]">{{ $order->order_number }}</td>
                        <td>{{ $order->customer_name }}</td>
                        <td class="font-medium">{{ number_format($order->total) }} EGP</td>
                        <td><span class="dj-admin-badge {{ $djOrderBadge }}">{{ __('orders.status_'.$order->status) }}</span></td>
                        <td>{{ $order->payment ? __('orders.payment_status_'.$order->payment->status) : '-' }}</td>
                        <td>{{ $order->created_at->translatedFormat('M j, Y') }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.orders.show', $order) }}" class="dj-admin-link">{{ __('orders.view') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="dj-admin-table-empty">{{ __('orders.no_orders') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $orders->links() }}</div>
@endsection
