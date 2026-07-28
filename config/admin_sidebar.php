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
            ['label' => 'nav.order_change_requests', 'route' => 'admin.order-change-requests.index', 'match' => 'admin.order-change-requests.*', 'permission' => 'orders.view'],
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
            // Product Images and Variants used to be listed here as their
            // own "Soon" placeholders, but both shipped this session —
            // they're just not separate top-level pages. Every product's
            // Images and Variants tabs (Admin\ProductController::edit(),
            // see resources/views/admin/products/edit.blade.php) are the
            // real, live feature; a second, redundant top-level nav entry
            // for either was never actually the plan, so removed rather
            // than left as a placeholder that would never get filled in.
            ['label' => 'nav.inventory', 'route' => 'admin.inventory.index', 'match' => 'admin.inventory.*', 'permission' => 'inventory.view'],
            ['label' => 'nav.reviews', 'route' => 'admin.reviews.index', 'match' => 'admin.reviews.*', 'permission' => 'reviews.view'],
        ],
    ],
    [
        'label' => 'nav.marketing',
        'icon' => 'megaphone',
        'items' => [
            ['label' => 'nav.wishlist', 'route' => 'admin.wishlist-analytics.index', 'match' => 'admin.wishlist-analytics.*', 'permission' => 'reports.wishlist'],
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
            // No permission gate — matches Dashboard's precedent of "no
            // permission key = visible to any staff member". The real
            // access control here is the environment check in
            // EmailPreviewController itself (a local-only dev/QA tool);
            // adding an RBAC permission on top would be gating a feature
            // that's already fully inert in production regardless.
            ['label' => 'nav.emails', 'route' => 'admin.email-preview.index', 'match' => 'admin.email-preview.*'],
        ],
    ],
    [
        'label' => 'nav.content',
        'icon' => 'document',
        'items' => [
            ['label' => 'nav.blog', 'route' => 'admin.blog.index', 'match' => 'admin.blog.*', 'permission' => 'blog.view'],
            ['label' => 'nav.blog_comments', 'route' => 'admin.blog-comments.index', 'match' => 'admin.blog-comments.*', 'permission' => 'comments.view'],
            ['label' => 'nav.services', 'route' => 'admin.services.index', 'match' => 'admin.services.*', 'permission' => 'pages.manage'],
            ['label' => 'nav.faq', 'route' => 'admin.faqs.index', 'match' => 'admin.faqs.*', 'permission' => 'pages.manage'],
            // Testimonials ARE featured reviews (Review::is_featured,
            // ReviewController::feature()/unfeature()) — there's no
            // separate testimonials table/page, so this points at the
            // same real Reviews screen as Catalog > Reviews above, which
            // now has a "Featured" filter/stat card to manage them.
            ['label' => 'nav.testimonials', 'route' => 'admin.reviews.index', 'match' => 'admin.reviews.*', 'permission' => 'reviews.view'],
            ['label' => 'nav.hero_banners', 'route' => 'admin.hero-banners.index', 'match' => 'admin.hero-banners.*', 'permission' => 'banners.manage'],
        ],
    ],
    [
        'label' => 'nav.reports',
        'icon' => 'chart-pie',
        'items' => [
            ['label' => 'nav.reports_sales', 'route' => 'admin.reports.sales', 'match' => 'admin.reports.sales*', 'permission' => 'reports.sales'],
            ['label' => 'nav.reports_products', 'route' => 'admin.reports.products', 'match' => 'admin.reports.products', 'permission' => 'reports.products'],
            ['label' => 'nav.reports_customers', 'route' => 'admin.reports.customers', 'match' => 'admin.reports.customers', 'permission' => 'reports.customers'],
            // Same shared WishlistAnalyticsController page as Marketing >
            // Wishlist above (config/admin_sidebar.php's Marketing group) —
            // both entries were pre-wired to the identical reports.wishlist
            // permission slug, which is why this links to that one page
            // rather than a second, duplicate wishlist screen.
            ['label' => 'nav.reports_wishlist', 'route' => 'admin.wishlist-analytics.index', 'match' => 'admin.wishlist-analytics.*', 'permission' => 'reports.wishlist'],
            ['label' => 'nav.reports_inventory', 'route' => 'admin.reports.inventory', 'match' => 'admin.reports.inventory', 'permission' => 'reports.inventory'],
        ],
    ],
    [
        'label' => 'nav.settings',
        'icon' => 'cog',
        'items' => [
            ['label' => 'nav.settings_website', 'route' => 'admin.settings.edit', 'match' => 'admin.settings.*', 'permission' => 'settings.view'],
            ['label' => 'nav.settings_payments', 'route' => null, 'permission' => 'payment_settings.edit'],
            ['label' => 'nav.settings_shipping', 'route' => 'admin.shipping-methods.index', 'match' => 'admin.shipping-methods.*', 'permission' => 'shipping_settings.edit'],
            // Facebook/Instagram URLs are real, working fields on the
            // Website settings form (SettingController::edit()) — there's
            // no separate "social settings" page, so this points at the
            // same real route as Website above rather than staying a
            // placeholder for a page that would just duplicate it.
            ['label' => 'nav.settings_social', 'route' => 'admin.settings.edit', 'match' => 'admin.settings.*', 'permission' => 'settings.view'],
            ['label' => 'nav.settings_admin_users', 'route' => 'admin.users.index', 'match' => 'admin.users.*', 'permission' => 'users.view'],
            ['label' => 'nav.settings_roles', 'route' => 'admin.roles.index', 'match' => 'admin.roles.*', 'permission' => 'roles.view'],
            ['label' => 'nav.settings_permissions', 'route' => 'admin.permissions.index', 'match' => 'admin.permissions.*', 'permission' => 'permissions.view'],
            // Reuses 'users.view' rather than a new permission slug —
            // hasAdminAccess() hardcodes users./roles./permissions. as
            // the three Super-Admin-exclusive prefixes, so a new slug
            // here wouldn't get that exclusion for free and would need
            // touching that method just for this one nav entry. The
            // route itself is what actually enforces Super-Admin-only
            // access (see routes/admin.php's super_admin middleware
            // group) — same as Users/Roles/Permissions above.
            ['label' => 'nav.settings_activity_log', 'route' => 'admin.activity-log.index', 'match' => 'admin.activity-log.*', 'permission' => 'users.view'],
        ],
    ],
];
