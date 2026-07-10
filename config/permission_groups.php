<?php

/**
 * Every permission slug in the system, grouped for the admin Permissions
 * UI (rendered as fieldsets) and consumed by PermissionSeeder. The
 * grouping has no meaning to Spatie itself — purely presentational/seeding
 * organization. Group keys map to translation labels under
 * permissions.groups.<key> (lang/{en,ar}/permissions.php).
 */

return [
    'dashboard' => ['dashboard.view'],

    'users' => [
        'users.view', 'users.create', 'users.edit', 'users.delete',
        'users.assign_roles', 'users.assign_permissions',
        'users.reset_password', 'users.force_logout', 'users.toggle_active',
    ],

    'customers' => [
        'customers.view', 'customers.create', 'customers.edit', 'customers.delete',
        'customers.notes', 'customers.disable', 'customers.carts_view',
        'customers.wishlist_view', 'customers.send_reminder',
    ],

    'products' => [
        'products.view', 'products.create', 'products.edit', 'products.delete',
        'products.publish', 'products.manage_images', 'products.manage_variants',
    ],

    'categories' => [
        'categories.view', 'categories.create', 'categories.edit', 'categories.delete',
    ],

    'orders' => [
        'orders.view', 'orders.edit', 'orders.update_status', 'orders.cancel',
        'orders.delete', 'orders.invoice', 'orders.refund',
    ],

    'inventory' => [
        'inventory.view', 'inventory.update', 'inventory.adjust', 'inventory.history',
    ],

    'reports' => [
        'reports.view', 'reports.sales', 'reports.revenue', 'reports.customers',
        'reports.products', 'reports.inventory',
    ],

    'settings' => [
        'settings.view', 'settings.edit', 'payment_settings.edit', 'shipping_settings.edit',
    ],

    'content' => [
        'blog.view', 'blog.create', 'blog.edit', 'blog.delete',
        'banners.manage', 'pages.manage',
    ],

    'marketing' => [
        'coupons.view', 'coupons.create', 'coupons.edit', 'coupons.delete',
        'newsletter.view', 'newsletter.send',
    ],

    'communication' => [
        'messages.view', 'messages.reply', 'notifications.view', 'notifications.send',
    ],

    'reviews' => [
        'reviews.view', 'reviews.approve', 'reviews.reject', 'reviews.delete',
    ],

    'comments' => [
        'comments.view', 'comments.approve', 'comments.reject', 'comments.delete',
    ],

    'carts' => [
        'carts.view', 'carts.send_reminder',
    ],

    'roles_permissions' => [
        'roles.view', 'permissions.view',
    ],
];
