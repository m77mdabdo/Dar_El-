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
        'new_order' => ['bg' => 'rgba(47,122,77,.12)', 'fg' => '#2f7a4d', 'path' => 'M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h13.5'],
        'low_stock' => ['bg' => 'rgba(232,195,154,.4)', 'fg' => '#8a5a2a', 'path' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z'],
        'out_of_stock' => ['bg' => 'rgba(156,80,100,.15)', 'fg' => '#9C5064', 'path' => 'M9.75 9.75l4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
        'new_customer' => ['bg' => 'rgba(96,21,38,.09)', 'fg' => '#601526', 'path' => 'M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z'],
        'new_contact_message' => ['bg' => 'rgba(212,165,116,.3)', 'fg' => '#7A2038', 'path' => 'M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75'],
        'newsletter_subscription' => ['bg' => 'rgba(60,11,23,.07)', 'fg' => '#3C0B17', 'path' => 'M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75'],
        'order_cancelled' => ['bg' => 'rgba(156,80,100,.15)', 'fg' => '#9C5064', 'path' => 'M9.75 9.75l4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
        'default' => ['bg' => 'var(--dj-cream)', 'fg' => '#a67b83', 'path' => 'M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0'],
    ];
    $djIcon = $djIconByType[$djType] ?? $djIconByType['default'];
@endphp
<div
    data-notification-item
    class="dj-admin-notif-item {{ $djUnread ? 'dj-admin-notif-unread' : '' }}"
>
    <span class="dj-admin-notif-icon" style="background:{{ $djIcon['bg'] }}; color:{{ $djIcon['fg'] }};">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $djIcon['path'] }}"/></svg>
    </span>

    <div class="min-w-0 flex-1">
        @if ($djUrl)
            <a href="{{ $djUrl }}" class="dj-admin-notif-title">{{ $djLabel }}</a>
        @else
            <p class="dj-admin-notif-title">{{ $djLabel }}</p>
        @endif
        @if ($djMessage !== '')
            <p class="dj-admin-notif-message truncate">{{ $djMessage }}</p>
        @endif
        <p class="dj-admin-notif-time">{{ $notification->created_at->diffForHumans() }}</p>
    </div>

    @if ($djUnread)
        <button
            type="button"
            data-mark-read-btn
            onclick="adminMarkNotificationRead(this, '{{ $notification->id }}', '{{ route('admin.notifications.read', $notification->id) }}')"
            class="dj-admin-notif-mark shrink-0"
        >
            {{ __('admin.notifications.mark_read') }}
        </button>
    @endif
</div>
