@extends('admin.layout')

@section('title', __('customers.customer_wishlist'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.customers.show', $customer) }}" class="dj-admin-link">&larr; {{ $customer->name }}</a>
    </div>

    <div class="dj-admin-card dj-admin-table-wrap">
        <table class="dj-admin-table">
            <thead>
                <tr>
                    <th></th>
                    <th>{{ __('general.name') }}</th>
                    <th>{{ __('products.price') }}</th>
                    <th>{{ __('customers.stock_status') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($wishlist as $w)
                    @continue(! $w->product)
                    <tr>
                        <td>
                            @if ($w->product->cover_image_src)
                                <img src="{{ $w->product->cover_image_src }}" class="w-12 h-12 rounded-lg object-cover border border-[var(--dj-cream-2)]">
                            @endif
                        </td>
                        <td class="font-medium text-[var(--dj-ink)]">{{ trans_field($w->product, 'name') }}</td>
                        <td>{{ number_format($w->product->price) }} EGP</td>
                        <td>
                            @php $djStatus = $w->product->stockStatus($w->product->totalStock()); @endphp
                            <span class="dj-admin-badge {{ $djStatus['status'] === 'out_of_stock' ? 'dj-admin-badge-danger' : ($djStatus['status'] === 'low_stock' ? 'dj-admin-badge-gold' : 'dj-admin-badge-success') }}">{{ $djStatus['label'] }}</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="dj-admin-table-empty">{{ __('customers.no_wishlist_items') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $wishlist->links() }}</div>
@endsection
