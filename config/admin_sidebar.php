<?php

/**
 * Admin sidebar navigation tree.
 *
 * Each top-level entry is either a direct link (has 'route') or a
 * collapsible group (has 'items'). Every leaf item may have:
 *   - label:  translation key under admin.nav.*
 *   - route:  a named route, or null for a not-yet-built page
 *   - match:  a route-name wildcard used to highlight the active state
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
            ['label' => 'nav.orders', 'route' => 'admin.orders.index', 'match' => 'admin.orders.*'],
            ['label' => 'nav.customers', 'route' => null],
            ['label' => 'nav.carts', 'route' => null],
        ],
    ],
    [
        'label' => 'nav.catalog',
        'icon' => 'tag',
        'items' => [
            ['label' => 'nav.products', 'route' => 'admin.products.index', 'match' => 'admin.products.*'],
            ['label' => 'nav.categories', 'route' => 'admin.categories.index', 'match' => 'admin.categories.*'],
            ['label' => 'nav.product_images', 'route' => null],
            ['label' => 'nav.variants', 'route' => null],
            ['label' => 'nav.inventory', 'route' => null],
            ['label' => 'nav.reviews', 'route' => 'admin.reviews.index', 'match' => 'admin.reviews.*'],
        ],
    ],
    [
        'label' => 'nav.marketing',
        'icon' => 'megaphone',
        'items' => [
            ['label' => 'nav.wishlist', 'route' => null],
            ['label' => 'nav.newsletter', 'route' => 'admin.newsletter.index', 'match' => 'admin.newsletter.*'],
            ['label' => 'nav.coupons', 'route' => 'admin.coupons.index', 'match' => 'admin.coupons.*'],
        ],
    ],
    [
        'label' => 'nav.communication',
        'icon' => 'chat',
        'items' => [
            ['label' => 'nav.contact_messages', 'route' => 'admin.contact-messages.index', 'match' => 'admin.contact-messages.*'],
            ['label' => 'nav.notifications', 'route' => 'admin.notifications.index', 'match' => 'admin.notifications.*'],
            ['label' => 'nav.emails', 'route' => null],
        ],
    ],
    [
        'label' => 'nav.content',
        'icon' => 'document',
        'items' => [
            ['label' => 'nav.blog', 'route' => 'admin.blog.index', 'match' => 'admin.blog.*'],
            ['label' => 'nav.services', 'route' => null],
            ['label' => 'nav.faq', 'route' => null],
            ['label' => 'nav.testimonials', 'route' => null],
            ['label' => 'nav.hero_banners', 'route' => null],
        ],
    ],
    [
        'label' => 'nav.reports',
        'icon' => 'chart-pie',
        'items' => [
            ['label' => 'nav.reports_sales', 'route' => null],
            ['label' => 'nav.reports_products', 'route' => null],
            ['label' => 'nav.reports_customers', 'route' => null],
            ['label' => 'nav.reports_wishlist', 'route' => null],
            ['label' => 'nav.reports_inventory', 'route' => null],
        ],
    ],
    [
        'label' => 'nav.settings',
        'icon' => 'cog',
        'items' => [
            ['label' => 'nav.settings_website', 'route' => 'admin.settings.edit', 'match' => 'admin.settings.*'],
            ['label' => 'nav.settings_payments', 'route' => null],
            ['label' => 'nav.settings_shipping', 'route' => null],
            ['label' => 'nav.settings_social', 'route' => null],
            ['label' => 'nav.settings_admin_users', 'route' => null],
            ['label' => 'nav.settings_roles', 'route' => null],
            ['label' => 'nav.settings_permissions', 'route' => null],
        ],
    ],
];
