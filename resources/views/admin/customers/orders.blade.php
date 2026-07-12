@extends('admin.layout')

@section('title', __('customers.customer_orders'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.customers.show', $customer) }}" class="dj-admin-link">&larr; {{ $customer->name }}</a>
    </div>

    <div class="dj-admin-card dj-admin-table-wrap">
        <table class="dj-admin-table">
            <thead>
                <tr>
                    <th>{{ __('orders.order_number') }}</th>
                    <th>{{ __('general.date') }}</th>
                    <th>{{ __('general.status') }}</th>
                    <th>{{ __('orders.total') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td class="font-semibold text-[var(--dj-maroon)]">{{ $order->order_number }}</td>
                        <td>{{ $order->created_at->translatedFormat('M j, Y') }}</td>
                        <td><span class="dj-admin-badge dj-admin-badge-info">{{ __('orders.status_'.$order->status) }}</span></td>
                        <td>{{ number_format($order->total) }} EGP</td>
                        <td class="text-end"><a href="{{ route('admin.orders.show', $order) }}" class="dj-admin-link">{{ __('general.view') }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="dj-admin-table-empty">{{ __('customers.no_recent_orders') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $orders->links() }}</div>
@endsection
