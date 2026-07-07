@php
    $djData = $notification->data;
    $djType = $djData['type'] ?? 'default';
    $djUnread = is_null($notification->read_at);

    [$djLabel, $djMessage, $djUrl] = match ($djType) {
        'new_order' => [
            __('admin.notifications.type_new_order'),
            '#'.($djData['order_number'] ?? ''),
            isset($djData['order_id']) ? route('admin.orders.show', $djData['order_id']) : null,
        ],
        'low_stock' => [
            __('admin.notifications.type_low_stock'),
            trim(($djData['product_name'] ?? '').' — '.($djData['size'] ?? '')),
            route('admin.products.index', ['stock_status' => 'low_stock']),
        ],
        'out_of_stock' => [
            __('admin.notifications.type_out_of_stock'),
            trim(($djData['product_name'] ?? '').' — '.($djData['size'] ?? '')),
            route('admin.products.index', ['stock_status' => 'out_of_stock']),
        ],
        'new_customer' => [
            __('admin.notifications.type_new_customer'),
            $djData['customer_name'] ?? '',
            null,
        ],
        'new_contact_message' => [
            __('admin.notifications.type_new_contact_message'),
            $djData['name'] ?? '',
            route('admin.contact-messages.index'),
        ],
        'newsletter_subscription' => [
            __('admin.notifications.type_newsletter_subscription'),
            $djData['email'] ?? '',
            route('admin.newsletter.index'),
        ],
        'order_cancelled' => [
            __('admin.notifications.type_order_cancelled'),
            '#'.($djData['order_number'] ?? ''),
            isset($djData['order_id']) ? route('admin.orders.show', $djData['order_id']) : null,
        ],
        default => [__('admin.notifications.type_default'), '', null],
    };

    $djIconByType = [
        'new_order' => ['bg-emerald-50', 'text-emerald-600'],
        'low_stock' => ['bg-amber-50', 'text-amber-600'],
        'out_of_stock' => ['bg-red-50', 'text-red-600'],
        'new_customer' => ['bg-sky-50', 'text-sky-600'],
        'new_contact_message' => ['bg-violet-50', 'text-violet-600'],
        'newsletter_subscription' => ['bg-teal-50', 'text-teal-600'],
        'order_cancelled' => ['bg-red-50', 'text-red-600'],
        'default' => ['bg-stone-100', 'text-stone-500'],
    ];
    [$djBg, $djFg] = $djIconByType[$djType] ?? $djIconByType['default'];
@endphp
<div
    data-notification-item
    class="flex items-start gap-3 px-4 py-3 {{ $djUnread ? 'dj-admin-notif-unread bg-rose-50/40' : '' }} {{ $loop->last ?? true ? '' : 'border-b border-stone-100' }}"
>
    <span class="w-9 h-9 rounded-full {{ $djBg }} {{ $djFg }} flex items-center justify-center shrink-0">
        <svg class="w-4.5 h-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/></svg>
    </span>

    <div class="min-w-0 flex-1">
        @if ($djUrl)
            <a href="{{ $djUrl }}" class="text-sm font-semibold text-stone-800 hover:text-rose-700">{{ $djLabel }}</a>
        @else
            <p class="text-sm font-semibold text-stone-800">{{ $djLabel }}</p>
        @endif
        @if ($djMessage !== '')
            <p class="text-xs text-stone-500 truncate">{{ $djMessage }}</p>
        @endif
        <p class="text-[11px] text-stone-400 mt-0.5">{{ $notification->created_at->diffForHumans() }}</p>
    </div>

    @if ($djUnread)
        <button
            type="button"
            data-mark-read-btn
            onclick="adminMarkNotificationRead(this, '{{ $notification->id }}', '{{ route('admin.notifications.read', $notification->id) }}')"
            class="shrink-0 text-[11px] text-rose-700 hover:underline whitespace-nowrap"
        >
            {{ __('admin.notifications.mark_read') }}
        </button>
    @endif
</div>
