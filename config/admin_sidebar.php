<?php

/**
 * Admin sidebar navigation tree.
 *
 * Each top-level entry is either a direct link (has 'route') or a
 * collapsible group (has 'items'). Every leaf item may have:
 *   - label:      translation key under admin.nav.*
 *   - route:      a named route, or null for a not-yet-built page
 *   - match:      a route-name wildcard used to highlight the active state
 *   - permission: a permission slug checked via User::hasAdminAccess() —
 *                 omitted/null means always visible to any staff member
 *                 (e.g. Dashboard). A whole group is hidden if every one
 *                 of its items is hidden.
 *
 * Items with route === null render as disabled placeholders tagged
 * "Soon" instead of dead links — later phases just fill in the route
 * name here and the item becomes a real link with zero template changes.
 */

return [
    [
        'label' => 'nav.dashboard',
        'route' => 'admin.dashboard',
        'match' => 'admin.dashboard',
        'icon' => 'home',
    ],
    [
        'label' => 'nav.sales',
        'icon' => 'chart-bar',
        'items' => [
            ['label' => 'nav.orders', 'route' => 'admin.orders.index', 'match' => 'admin.orders.*', 'permission' => 'orders.view'],
            ['label' => 'nav.customers', 'route' => 'admin.customers.index', 'match' => 'admin.customers.*', 'permission' => 'customers.view'],
            ['label' => 'nav.carts', 'route' => 'admin.carts.index', 'match' => 'admin.carts.*', 'permission' => 'carts.view'],
        ],
    ],
    [
        'label' => 'nav.catalog',
        'icon' => 'tag',
        'items' => [
            ['label' => 'nav.products', 'route' => 'admin.products.index', 'match' => 'admin.products.*', 'permission' => 'products.view'],
            ['label' => 'nav.categories', 'route' => 'admin.categories.index', 'match' => 'admin.categories.*', 'permission' => 'categories.view'],
            ['label' => 'nav.product_images', 'route' => null, 'permission' => 'products.manage_images'],
            ['label' => 'nav.variants', 'route' => null, 'permission' => 'products.manage_variants'],
            ['label' => 'nav.inventory', 'route' => null, 'permission' => 'inventory.view'],
            ['label' => 'nav.reviews', 'route' => 'admin.reviews.index', 'match' => 'admin.reviews.*', 'permission' => 'reviews.view'],
        ],
    ],
    [
        'label' => 'nav.marketing',
        'icon' => 'megaphone',
        'items' => [
            ['label' => 'nav.wishlist', 'route' => null, 'permission' => 'reports.wishlist'],
            ['label' => 'nav.newsletter', 'route' => 'admin.newsletter.index', 'match' => 'admin.newsletter.*', 'permission' => 'newsletter.view'],
            ['label' => 'nav.coupons', 'route' => 'admin.coupons.index', 'match' => 'admin.coupons.*', 'permission' => 'coupons.view'],
        ],
    ],
    [
        'label' => 'nav.communication',
        'icon' => 'chat',
        'items' => [
            ['label' => 'nav.contact_messages', 'route' => 'admin.contact-messages.index', 'match' => 'admin.contact-messages.*', 'permission' => 'messages.view'],
            ['label' => 'nav.notifications', 'route' => 'admin.notifications.index', 'match' => 'admin.notifications.*', 'permission' => 'notifications.view'],
            ['label' => 'nav.emails', 'route' => null],
        ],
    ],
    [
        'label' => 'nav.content',
        'icon' => 'document',
        'items' => [
            ['label' => 'nav.blog', 'route' => 'admin.blog.index', 'match' => 'admin.blog.*', 'permission' => 'blog.view'],
            ['label' => 'nav.blog_comments', 'route' => 'admin.blog-comments.index', 'match' => 'admin.blog-comments.*', 'permission' => 'comments.view'],
            ['label' => 'nav.services', 'route' => null],
            ['label' => 'nav.faq', 'route' => null],
            ['label' => 'nav.testimonials', 'route' => null],
            ['label' => 'nav.hero_banners', 'route' => null, 'permission' => 'banners.manage'],
        ],
    ],
    [
        'label' => 'nav.reports',
        'icon' => 'chart-pie',
        'items' => [
            ['label' => 'nav.reports_sales', 'route' => null, 'permission' => 'reports.sales'],
            ['label' => 'nav.reports_products', 'route' => null, 'permission' => 'reports.products'],
            ['label' => 'nav.reports_customers', 'route' => null, 'permission' => 'reports.customers'],
            ['label' => 'nav.reports_wishlist', 'route' => null, 'permission' => 'reports.wishlist'],
            ['label' => 'nav.reports_inventory', 'route' => null, 'permission' => 'reports.inventory'],
        ],
    ],
    [
        'label' => 'nav.settings',
        'icon' => 'cog',
        'items' => [
            ['label' => 'nav.settings_website', 'route' => 'admin.settings.edit', 'match' => 'admin.settings.*', 'permission' => 'settings.view'],
            ['label' => 'nav.settings_payments', 'route' => null, 'permission' => 'payment_settings.edit'],
            ['label' => 'nav.settings_shipping', 'route' => null, 'permission' => 'shipping_settings.edit'],
            ['label' => 'nav.settings_social', 'route' => null, 'permission' => 'settings.view'],
            ['label' => 'nav.settings_admin_users', 'route' => 'admin.users.index', 'match' => 'admin.users.*', 'permission' => 'users.view'],
            ['label' => 'nav.settings_roles', 'route' => 'admin.roles.index', 'match' => 'admin.roles.*', 'permission' => 'roles.view'],
            ['label' => 'nav.settings_permissions', 'route' => 'admin.permissions.index', 'match' => 'admin.permissions.*', 'permission' => 'permissions.view'],
        ],
    ],
];
