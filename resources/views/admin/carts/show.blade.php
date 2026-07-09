@extends('admin.layout')

@section('title', __('carts.cart_details'))

@section('content')
    @php
        $djCartBadge = match ($cart->status) {
            'converted' => 'dj-admin-badge-success',
            'abandoned' => 'dj-admin-badge-gold',
            'expired' => 'dj-admin-badge-neutral',
            default => 'dj-admin-badge-info',
        };
    @endphp

    <div class="mb-6 flex items-center justify-between flex-wrap gap-3">
        <a href="{{ route('admin.carts.index') }}" class="dj-admin-link">&larr; {{ __('carts.title') }}</a>
        <span class="dj-admin-badge {{ $djCartBadge }}">{{ __('carts.status_'.$cart->status) }}</span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
        {{-- Main column --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Customer info --}}
            <div class="dj-admin-card p-4 sm:p-6">
                <h2 class="font-semibold mb-3 text-sm sm:text-base text-[var(--dj-maroon-dark)]">{{ __('carts.customer') }}</h2>
                @if ($cart->user)
                    <p class="font-medium text-[var(--dj-ink)]">
                        <a href="{{ route('admin.customers.show', $cart->user) }}" class="dj-admin-link">{{ $cart->user->name }}</a>
                    </p>
                    <p class="text-sm text-[var(--dj-rose-dust)]">{{ $cart->user->email }}</p>
                    @if ($cart->user->phone)
                        <p class="text-sm text-[var(--dj-rose-dust)]">{{ $cart->user->phone }}</p>
                    @endif
                @endif
            </div>

            {{-- Items --}}
            <div class="dj-admin-card dj-admin-table-wrap">
                <table class="dj-admin-table">
                    <thead>
                        <tr>
                            <th></th>
                            <th>{{ __('general.name') }}</th>
                            <th>{{ __('carts.size') }}</th>
                            <th>{{ __('carts.quantity') }}</th>
                            <th>{{ __('carts.unit_price') }}</th>
                            <th>{{ __('carts.total') }}</th>
                            <th>{{ __('carts.stock_availability') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($cart->items as $item)
                            <tr>
                                <td>
                                    @if ($item->image_snapshot)
                                        <img src="{{ $item->image_snapshot }}" class="w-12 h-12 rounded-lg object-cover border border-[var(--dj-cream-2)]">
                                    @endif
                                </td>
                                <td class="font-medium text-[var(--dj-ink)]">
                                    @if ($item->product)
                                        <a href="{{ route('admin.products.edit', $item->product) }}" class="dj-admin-link">{{ $item->product_name }}</a>
                                    @else
                                        {{ $item->product_name }}
                                    @endif
                                </td>
                                <td>{{ $item->variant_snapshot['size'] ?? '-' }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td>{{ number_format($item->price) }} EGP</td>
                                <td>{{ number_format($item->total) }} EGP</td>
                                <td>
                                    @if ($item->product)
                                        @php $djStockStatus = $item->product->stockStatus($item->product->totalStock()); @endphp
                                        <span class="dj-admin-badge {{ $djStockStatus['status'] === 'out_of_stock' ? 'dj-admin-badge-danger' : ($djStockStatus['status'] === 'low_stock' ? 'dj-admin-badge-gold' : 'dj-admin-badge-success') }}">{{ $djStockStatus['label'] }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="dj-admin-table-empty">{{ __('carts.no_carts_found') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Reminder history --}}
            <div class="dj-admin-card p-4 sm:p-6">
                <h2 class="font-semibold mb-3 text-sm sm:text-base text-[var(--dj-maroon-dark)]">{{ __('carts.reminder_history') }}</h2>
                @if ($cart->reminders->isEmpty())
                    <p class="text-sm text-[var(--dj-rose-dust)]">{{ __('carts.no_carts_found') }}</p>
                @else
                    <div class="dj-admin-table-wrap">
                        <table class="dj-admin-table">
                            <thead>
                                <tr>
                                    <th>{{ __('general.date') }}</th>
                                    <th>{{ __('carts.channel_mail') }} / {{ __('carts.channel_database') }}</th>
                                    <th>{{ __('general.status') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($cart->reminders as $reminder)
                                    <tr>
                                        <td>{{ $reminder->sent_at?->format('M j, Y H:i') ?? $reminder->created_at->format('M j, Y H:i') }}</td>
                                        <td>{{ __('carts.channel_'.$reminder->channel) }}</td>
                                        <td>
                                            <span class="dj-admin-badge {{ $reminder->status === 'sent' ? 'dj-admin-badge-success' : 'dj-admin-badge-danger' }}">{{ __('carts.reminder_status_'.$reminder->status) }}</span>
                                            @if ($reminder->status === 'failed' && $reminder->error_message)
                                                <p class="text-xs text-[var(--dj-rose-dust)] mt-1">{{ $reminder->error_message }}</p>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            <div class="dj-admin-card p-4 sm:p-6 space-y-3">
                <h2 class="font-semibold mb-1 text-sm sm:text-base text-[var(--dj-maroon-dark)]">{{ __('carts.cart_details') }}</h2>
                <div class="flex justify-between text-sm"><span class="text-[var(--dj-rose-dust)]">{{ __('carts.cart_subtotal') }}</span><span class="font-medium">{{ number_format($cart->subtotal) }} EGP</span></div>
                <div class="flex justify-between text-sm"><span class="text-[var(--dj-rose-dust)]">{{ __('carts.estimated_total') }}</span><span class="font-medium">{{ number_format($cart->total) }} EGP</span></div>
                <div class="flex justify-between text-sm"><span class="text-[var(--dj-rose-dust)]">{{ __('carts.last_updated') }}</span><span class="font-medium">{{ $cart->last_activity_at->format('M j, Y H:i') }}</span></div>
                @if ($cart->status === 'abandoned')
                    <div class="flex justify-between text-sm"><span class="text-[var(--dj-rose-dust)]">{{ __('carts.abandoned_duration') }}</span><span class="font-medium">{{ $cart->abandonedDuration() }}</span></div>
                @endif
                <div class="flex justify-between text-sm"><span class="text-[var(--dj-rose-dust)]">{{ __('carts.reminder_count') }}</span><span class="font-medium">{{ $cart->reminder_count }}</span></div>
                @if ($cart->last_reminder_sent_at)
                    <div class="flex justify-between text-sm"><span class="text-[var(--dj-rose-dust)]">{{ __('carts.last_reminder') }}</span><span class="font-medium">{{ $cart->last_reminder_sent_at->format('M j, Y H:i') }}</span></div>
                @endif

                @if ($cart->order)
                    <a href="{{ route('admin.orders.show', $cart->order) }}" class="dj-admin-btn dj-admin-btn-secondary w-full text-center block">{{ __('carts.view_order') }}</a>
                @elseif ($cart->status !== 'converted' && $cart->items_count > 0)
                    <form method="POST" action="{{ route('admin.carts.sendReminder', $cart) }}">
                        @csrf
                        <button class="dj-admin-btn dj-admin-btn-primary w-full">{{ __('carts.send_reminder') }}</button>
                    </form>
                @endif

                @if ($cart->user)
                    <a href="{{ route('admin.customers.show', $cart->user) }}" class="dj-admin-link block text-center">{{ __('carts.view_customer') }}</a>
                @endif
            </div>
        </div>
    </div>
@endsection
