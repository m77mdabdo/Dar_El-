<?php

/**
 * Named permission bundles offered as one-click "apply preset" buttons on
 * the Employee create/edit form — purely a UI convenience (checks the
 * matching boxes client-side via Alpine), not a stored/DB concept. Keys
 * map to translation labels under users.presets.<key>.
 */

return [
    'product_manager' => [
        'dashboard.view',
        'products.view', 'products.create', 'products.edit', 'products.delete',
        'products.publish', 'products.manage_images', 'products.manage_variants',
        'categories.view', 'categories.create', 'categories.edit', 'categories.delete',
        'inventory.view', 'inventory.update',
    ],

    'order_manager' => [
        'dashboard.view',
        'orders.view', 'orders.edit', 'orders.update_status', 'orders.cancel', 'orders.invoice',
        'customers.view', 'customers.carts_view',
    ],

    'inventory_manager' => [
        'dashboard.view',
        'inventory.view', 'inventory.update', 'inventory.adjust', 'inventory.history',
        'products.view', 'products.manage_variants',
    ],

    'content_manager' => [
        'dashboard.view',
        'blog.view', 'blog.create', 'blog.edit', 'blog.delete',
        'banners.manage', 'pages.manage',
        'comments.view', 'comments.approve', 'comments.reject',
    ],

    'customer_support' => [
        'dashboard.view',
        'customers.view', 'customers.notes', 'customers.carts_view', 'customers.wishlist_view',
        'orders.view', 'orders.invoice',
        'messages.view', 'messages.reply',
    ],

    'marketing_manager' => [
        'dashboard.view',
        'coupons.view', 'coupons.create', 'coupons.edit', 'coupons.delete',
        'newsletter.view', 'newsletter.send',
        'reports.sales', 'reports.products',
    ],
];
