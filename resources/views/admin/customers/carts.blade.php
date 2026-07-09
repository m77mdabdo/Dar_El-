@extends('admin.layout')

@section('title', __('customers.customer_cart'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.customers.show', $customer) }}" class="dj-admin-link">&larr; {{ $customer->name }}</a>
    </div>

    <div class="dj-admin-card dj-admin-table-wrap">
        <table class="dj-admin-table">
            <thead>
                <tr>
                    <th>{{ __('carts.cart_items') }}</th>
                    <th>{{ __('carts.cart_total') }}</th>
                    <th>{{ __('general.status') }}</th>
                    <th>{{ __('carts.last_updated') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($carts as $cart)
                    <tr>
                        <td>{{ $cart->items_count }}</td>
                        <td>{{ number_format($cart->total) }} EGP</td>
                        <td><span class="dj-admin-badge {{ $cart->status === 'converted' ? 'dj-admin-badge-success' : ($cart->status === 'abandoned' ? 'dj-admin-badge-gold' : 'dj-admin-badge-info') }}">{{ __('carts.status_'.$cart->status) }}</span></td>
                        <td>{{ $cart->last_activity_at->format('M j, Y H:i') }}</td>
                        <td class="text-end"><a href="{{ route('admin.carts.show', $cart) }}" class="dj-admin-link">{{ __('general.view') }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="dj-admin-table-empty">{{ __('carts.no_carts_found') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $carts->links() }}</div>
@endsection
