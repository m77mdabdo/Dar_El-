@extends('admin.layout')

@section('title', __('customers.customer_details'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.customers.index') }}" class="dj-admin-link">&larr; {{ __('general.back') }}</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            {{-- Profile --}}
            <div class="dj-admin-card p-4 sm:p-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div>
                        <h2 class="font-semibold text-lg text-[var(--dj-maroon-dark)]">{{ $customer->name }}</h2>
                        <p class="text-sm text-[var(--dj-rose-dust)]">{{ $customer->email }} &middot; {{ $customer->phone }}</p>
                    </div>
                    <div class="flex gap-2 flex-wrap justify-end">
                        <span class="dj-admin-badge {{ $customer->email_verified_at ? 'dj-admin-badge-success' : 'dj-admin-badge-gold' }}">
                            {{ $customer->email_verified_at ? __('customers.verified') : __('customers.unverified') }}
                        </span>
                        <span class="dj-admin-badge {{ $customer->isDisabled() ? 'dj-admin-badge-danger' : 'dj-admin-badge-success' }}">
                            {{ $customer->isDisabled() ? __('customers.disabled') : __('customers.enabled') }}
                        </span>
                        <span class="dj-admin-badge dj-admin-badge-info">
                            {{ $customer->registrationMethodLabel() }}
                        </span>
                    </div>
                </div>
                <p class="text-xs text-[var(--dj-rose-dust)]">{{ __('customers.registered_at') }}: {{ $customer->created_at->translatedFormat('M j, Y') }}</p>

                <div class="flex flex-wrap gap-3 mt-4 pt-4 border-t border-[var(--dj-cream-2)]">
                    <a href="{{ route('admin.customers.orders', $customer) }}" class="dj-admin-btn dj-admin-btn-secondary dj-admin-btn-sm">{{ __('customers.view_orders') }}</a>
                    <a href="{{ route('admin.customers.carts', $customer) }}" class="dj-admin-btn dj-admin-btn-secondary dj-admin-btn-sm">{{ __('customers.view_cart') }}</a>
                    <a href="{{ route('admin.customers.wishlist', $customer) }}" class="dj-admin-btn dj-admin-btn-secondary dj-admin-btn-sm">{{ __('customers.view_wishlist') }}</a>

                    @if ($currentCart)
                        <form method="POST" action="{{ route('admin.customers.send-reminder', $customer) }}">
                            @csrf
                            <button class="dj-admin-btn dj-admin-btn-secondary dj-admin-btn-sm">{{ __('customers.send_reminder') }}</button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('admin.customers.toggle-disabled', $customer) }}" onsubmit="return confirm('{{ $customer->isDisabled() ? __('customers.confirm_enable') : __('customers.confirm_disable') }}')">
                        @csrf @method('PATCH')
                        <button class="dj-admin-btn dj-admin-btn-secondary dj-admin-btn-sm">{{ $customer->isDisabled() ? __('customers.enable_customer') : __('customers.disable_customer') }}</button>
                    </form>

                    <form method="POST" action="{{ route('admin.customers.destroy', $customer) }}" onsubmit="return confirm('{{ __('customers.confirm_delete') }}')">
                        @csrf @method('DELETE')
                        <button class="dj-admin-btn dj-admin-btn-danger dj-admin-btn-sm">{{ __('customers.delete_customer') }}</button>
                    </form>
                </div>
                @if (session('error'))
                    <p class="dj-admin-error mt-2">{{ session('error') }}</p>
                @endif
            </div>

            {{-- Stats --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                @foreach ([
                    ['label' => __('customers.total_orders'), 'value' => $stats['total_orders']],
                    ['label' => __('customers.completed_orders'), 'value' => $stats['completed_orders']],
                    ['label' => __('customers.cancelled_orders'), 'value' => $stats['cancelled_orders']],
                    ['label' => __('customers.pending_orders'), 'value' => $stats['pending_orders']],
                    ['label' => __('customers.total_spent'), 'value' => number_format($stats['total_spent']).' EGP'],
                    ['label' => __('customers.average_order_value'), 'value' => number_format($stats['average_order_value']).' EGP'],
                    ['label' => __('customers.wishlist_count'), 'value' => $stats['wishlist_count']],
                    ['label' => __('customers.cart_items_count'), 'value' => $stats['cart_items_count']],
                ] as $card)
                    <div class="dj-admin-stat-card">
                        <p class="dj-admin-stat-label truncate">{{ $card['label'] }}</p>
                        <p class="dj-admin-stat-value truncate">{{ $card['value'] }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Latest Orders --}}
            <div class="dj-admin-card dj-admin-table-wrap">
                <table class="dj-admin-table">
                    <thead>
                        <tr>
                            <th>{{ __('orders.order_number') }}</th>
                            <th>{{ __('general.date') }}</th>
                            <th>{{ __('general.status') }}</th>
                            <th>{{ __('carts.total') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentOrders as $order)
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

            {{-- Current Cart --}}
            <div class="dj-admin-card p-4 sm:p-6">
                <h3 class="font-semibold text-[var(--dj-maroon-dark)] mb-3">{{ __('customers.current_cart') }}</h3>
                @if ($currentCart)
                    <div class="space-y-2">
                        @foreach ($currentCart->items as $item)
                            <div class="flex items-center gap-3 text-sm border-b border-[var(--dj-cream-2)] pb-2">
                                @if ($item->image_snapshot)
                                    <img src="{{ $item->image_snapshot }}" class="w-10 h-10 rounded-lg object-cover border border-[var(--dj-cream-2)]">
                                @endif
                                <span class="flex-1">{{ $item->product ? trans_field($item->product, 'name') : $item->product_name }} @if(!empty($item->variant_snapshot['size'])) ({{ $item->variant_snapshot['size'] }}) @endif &times; {{ $item->quantity }}</span>
                                <span class="font-semibold text-[var(--dj-maroon)]">{{ number_format($item->total) }} EGP</span>
                            </div>
                        @endforeach
                    </div>
                    <p class="text-sm font-semibold text-[var(--dj-maroon-dark)] mt-3">{{ __('carts.cart_total') }}: {{ number_format($currentCart->total) }} EGP</p>
                    <p class="text-xs text-[var(--dj-rose-dust)]">{{ __('carts.last_updated') }}: {{ $currentCart->last_activity_at->translatedFormat('M j, Y H:i') }}</p>
                @else
                    <p class="dj-admin-table-empty">{{ __('customers.no_current_cart') }}</p>
                @endif
            </div>

            {{-- Wishlist --}}
            <div class="dj-admin-card p-4 sm:p-6">
                <h3 class="font-semibold text-[var(--dj-maroon-dark)] mb-3">{{ __('customers.wishlist') }}</h3>
                @if ($wishlist->isNotEmpty())
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        @foreach ($wishlist as $w)
                            @continue(! $w->product)
                            <div class="text-center">
                                @if ($w->product->cover_image_src)
                                    <img src="{{ $w->product->cover_image_src }}" class="w-full h-20 object-cover rounded-lg border border-[var(--dj-cream-2)] mb-1">
                                @endif
                                <p class="text-xs font-medium truncate">{{ trans_field($w->product, 'name') }}</p>
                                <p class="text-xs text-[var(--dj-maroon)]">{{ number_format($w->product->price) }} EGP</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="dj-admin-table-empty">{{ __('customers.no_wishlist_items') }}</p>
                @endif
            </div>
        </div>

        <div class="space-y-4">
            {{-- Login History --}}
            <div class="dj-admin-card p-4 sm:p-6">
                <h3 class="font-semibold text-[var(--dj-maroon-dark)] mb-3">{{ __('customers.login_history') }}</h3>
                <div class="space-y-3">
                    @forelse ($loginHistory as $entry)
                        <div class="border-t border-[var(--dj-cream-2)] pt-2 text-xs">
                            <p class="text-[var(--dj-ink)] font-medium">
                                {{ \Illuminate\Support\Carbon::parse($entry->data['time'])->translatedFormat('M j, Y H:i') }}
                                @if ($entry->data['provider'] ?? null)
                                    <span class="dj-admin-badge dj-admin-badge-info ms-1">{{ ucfirst($entry->data['provider']) }}</span>
                                @endif
                            </p>
                            <p class="text-[var(--dj-rose-dust)] mt-0.5">
                                {{ $entry->data['browser'] }} &middot; {{ $entry->data['device'] }} &middot; {{ $entry->data['ip'] }}
                            </p>
                        </div>
                    @empty
                        <p class="dj-admin-table-empty">{{ __('customers.no_login_history') }}</p>
                    @endforelse
                </div>
            </div>

            {{-- Notes --}}
            <div class="dj-admin-card p-4 sm:p-6">
                <h3 class="font-semibold text-[var(--dj-maroon-dark)] mb-3">{{ __('customers.notes') }}</h3>
                <form method="POST" action="{{ route('admin.customers.notes.store', $customer) }}" class="mb-4">
                    @csrf
                    <textarea name="note" rows="3" required placeholder="{{ __('customers.note_placeholder') }}" class="dj-admin-input"></textarea>
                    @error('note') <p class="dj-admin-error">{{ $message }}</p> @enderror
                    <button class="dj-admin-btn dj-admin-btn-primary dj-admin-btn-sm mt-2">{{ __('customers.add_note') }}</button>
                </form>
                <div class="space-y-3">
                    @forelse ($customer->customerNotes as $note)
                        <div class="border-t border-[var(--dj-cream-2)] pt-2">
                            <p class="text-sm text-[var(--dj-ink)]">{{ $note->note }}</p>
                            <p class="text-xs text-[var(--dj-rose-dust)] mt-1">{{ $note->admin?->name }} &middot; {{ $note->created_at->translatedFormat('M j, Y H:i') }}</p>
                        </div>
                    @empty
                        <p class="dj-admin-table-empty">{{ __('customers.no_notes') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
