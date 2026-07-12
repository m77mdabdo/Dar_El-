@extends('admin.layout')

@section('title', __('customers.title'))

@section('content')
    <form method="GET" class="flex flex-wrap gap-2 mb-4">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('customers.search_placeholder') }}" class="dj-admin-input w-full sm:w-auto">

        <select name="verified" onchange="this.form.submit()" class="dj-admin-input w-auto">
            <option value="">{{ __('customers.all_customers') }}</option>
            <option value="1" {{ request('verified') === '1' ? 'selected' : '' }}>{{ __('customers.verified_only') }}</option>
            <option value="0" {{ request('verified') === '0' ? 'selected' : '' }}>{{ __('customers.unverified_only') }}</option>
        </select>

        <select name="has_orders" onchange="this.form.submit()" class="dj-admin-input w-auto">
            <option value="">{{ __('customers.has_orders') }}?</option>
            <option value="1" {{ request('has_orders') === '1' ? 'selected' : '' }}>{{ __('customers.has_orders') }}</option>
        </select>

        <select name="has_abandoned_cart" onchange="this.form.submit()" class="dj-admin-input w-auto">
            <option value="">{{ __('customers.has_abandoned_cart') }}?</option>
            <option value="1" {{ request('has_abandoned_cart') === '1' ? 'selected' : '' }}>{{ __('customers.has_abandoned_cart') }}</option>
        </select>

        <select name="has_wishlist" onchange="this.form.submit()" class="dj-admin-input w-auto">
            <option value="">{{ __('customers.has_wishlist') }}?</option>
            <option value="1" {{ request('has_wishlist') === '1' ? 'selected' : '' }}>{{ __('customers.has_wishlist') }}</option>
        </select>

        <select name="new" onchange="this.form.submit()" class="dj-admin-input w-auto">
            <option value="">{{ __('customers.new_this_week') }}?</option>
            <option value="1" {{ request('new') === '1' ? 'selected' : '' }}>{{ __('customers.new_this_week') }}</option>
        </select>

        <input type="date" name="date_from" value="{{ request('date_from') }}" title="{{ __('customers.date_from') }}" class="dj-admin-input w-auto">
        <input type="date" name="date_to" value="{{ request('date_to') }}" title="{{ __('customers.date_to') }}" class="dj-admin-input w-auto">
        <input type="number" name="spent_min" value="{{ request('spent_min') }}" placeholder="{{ __('customers.spent_min') }}" class="dj-admin-input w-auto">
        <input type="number" name="spent_max" value="{{ request('spent_max') }}" placeholder="{{ __('customers.spent_max') }}" class="dj-admin-input w-auto">

        <button class="dj-admin-btn dj-admin-btn-secondary shrink-0">{{ __('general.search') }}</button>
    </form>

    <div class="dj-admin-card dj-admin-table-wrap">
        <table class="dj-admin-table">
            <thead>
                <tr>
                    <th>{{ __('general.name') }}</th>
                    <th>{{ __('general.email') }}</th>
                    <th>{{ __('customers.phone') }}</th>
                    <th>{{ __('customers.verification_status') }}</th>
                    <th>{{ __('customers.total_orders') }}</th>
                    <th>{{ __('customers.total_spent') }}</th>
                    <th>{{ __('customers.cart_status') }}</th>
                    <th>{{ __('customers.wishlist_count') }}</th>
                    <th>{{ __('customers.registered_at') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($customers as $customer)
                    @php $djCart = $customer->carts->first(); @endphp
                    <tr>
                        <td class="font-medium text-[var(--dj-ink)]">
                            {{ $customer->name }}
                            @if ($customer->isDisabled())
                                <span class="dj-admin-badge dj-admin-badge-danger">{{ __('customers.disabled') }}</span>
                            @endif
                        </td>
                        <td>{{ $customer->email }}</td>
                        <td>{{ $customer->phone }}</td>
                        <td>
                            <span class="dj-admin-badge {{ $customer->email_verified_at ? 'dj-admin-badge-success' : 'dj-admin-badge-gold' }}">
                                {{ $customer->email_verified_at ? __('customers.verified') : __('customers.unverified') }}
                            </span>
                        </td>
                        <td>{{ $customer->orders_count }}</td>
                        <td>{{ number_format($customer->total_spent ?? 0) }} EGP</td>
                        <td>
                            @if ($djCart)
                                <span class="dj-admin-badge {{ $djCart->status === 'abandoned' ? 'dj-admin-badge-gold' : 'dj-admin-badge-success' }}">{{ __('carts.status_'.$djCart->status) }}</span>
                            @else
                                <span class="dj-admin-badge dj-admin-badge-neutral">{{ __('customers.no_cart') }}</span>
                            @endif
                        </td>
                        <td>{{ $customer->wishlists_count }}</td>
                        <td>{{ $customer->created_at->translatedFormat('M j, Y') }}</td>
                        <td class="text-end space-x-3 rtl:space-x-reverse">
                            <a href="{{ route('admin.customers.show', $customer) }}" class="dj-admin-link">{{ __('customers.view_customer') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="dj-admin-table-empty">{{ __('customers.no_customers_found') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $customers->links() }}</div>
@endsection
