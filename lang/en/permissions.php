<?php

/**
 * Human-readable labels for every permission slug, structured so
 * __('permissions.'.$slug) resolves naturally (e.g. __('permissions.products.view')
 * looks up ['products']['view'] below — Laravel's dot-path traversal
 * splits on every dot, which conveniently matches each slug's own
 * "prefix.action" shape). 'groups' holds the fieldset legend labels for
 * config/permission_groups.php's group keys.
 */

return [
    'groups' => [
        'dashboard' => 'Dashboard',
        'users' => 'Users',
        'customers' => 'Customers',
        'products' => 'Products',
        'categories' => 'Categories',
        'orders' => 'Orders',
        'inventory' => 'Inventory',
        'reports' => 'Reports',
        'settings' => 'Settings',
        'content' => 'Content',
        'marketing' => 'Marketing',
        'communication' => 'Communication',
        'reviews' => 'Reviews',
        'comments' => 'Comments',
        'carts' => 'Carts',
        'roles_permissions' => 'Roles & Permissions',
    ],

    'dashboard' => [
        'view' => 'View Dashboard',
    ],

    'users' => [
        'view' => 'View Users',
        'create' => 'Create Users',
        'edit' => 'Edit Users',
        'delete' => 'Delete Users',
        'assign_roles' => 'Assign Roles',
        'assign_permissions' => 'Assign Permissions',
        'reset_password' => 'Reset User Passwords',
        'force_logout' => 'Force Logout',
        'toggle_active' => 'Activate/Deactivate Users',
    ],

    'customers' => [
        'view' => 'View Customers',
        'create' => 'Create Customers',
        'edit' => 'Edit Customers',
        'delete' => 'Delete Customers',
        'notes' => 'Add Customer Notes',
        'disable' => 'Disable Customers',
        'carts_view' => 'View Customer Carts',
        'wishlist_view' => 'View Customer Wishlists',
        'send_reminder' => 'Send Cart Reminders',
    ],

    'products' => [
        'view' => 'View Products',
        'create' => 'Create Products',
        'edit' => 'Edit Products',
        'delete' => 'Delete Products',
        'publish' => 'Publish Products',
        'manage_images' => 'Manage Product Images',
        'manage_variants' => 'Manage Product Variants',
    ],

    'categories' => [
        'view' => 'View Categories',
        'create' => 'Create Categories',
        'edit' => 'Edit Categories',
        'delete' => 'Delete Categories',
    ],

    'orders' => [
        'view' => 'View Orders',
        'edit' => 'Edit Orders',
        'update_status' => 'Update Order Status',
        'cancel' => 'Cancel Orders',
        'delete' => 'Delete Orders',
        'invoice' => 'View/Download Invoices',
        'refund' => 'Refund Orders',
    ],

    'inventory' => [
        'view' => 'View Inventory',
        'update' => 'Update Stock',
        'adjust' => 'Adjust Stock',
        'history' => 'View Stock History',
    ],

    'reports' => [
        'view' => 'View Reports',
        'sales' => 'View Sales Reports',
        'revenue' => 'View Revenue Reports',
        'customers' => 'View Customer Reports',
        'products' => 'View Product Reports',
        'inventory' => 'View Inventory Reports',
    ],

    'settings' => [
        'view' => 'View Settings',
        'edit' => 'Edit Website Settings',
    ],

    'payment_settings' => [
        'edit' => 'Edit Payment Settings',
    ],

    'shipping_settings' => [
        'edit' => 'Edit Shipping Settings',
    ],

    'blog' => [
        'view' => 'View Blog Posts',
        'create' => 'Create Blog Posts',
        'edit' => 'Edit Blog Posts',
        'delete' => 'Delete Blog Posts',
    ],

    'banners' => [
        'manage' => 'Manage Banners',
    ],

    'pages' => [
        'manage' => 'Manage Pages',
    ],

    'coupons' => [
        'view' => 'View Coupons',
        'create' => 'Create Coupons',
        'edit' => 'Edit Coupons',
        'delete' => 'Delete Coupons',
    ],

    'newsletter' => [
        'view' => 'View Newsletter Subscribers',
        'send' => 'Send Newsletter',
    ],

    'messages' => [
        'view' => 'View Contact Messages',
        'reply' => 'Reply to Messages',
    ],

    'notifications' => [
        'view' => 'View Notifications',
        'send' => 'Send Notifications',
    ],

    'reviews' => [
        'view' => 'View Reviews',
        'approve' => 'Approve Reviews',
        'reject' => 'Reject Reviews',
        'delete' => 'Delete Reviews',
    ],

    'comments' => [
        'view' => 'View Comments',
        'approve' => 'Approve Comments',
        'reject' => 'Reject Comments',
        'delete' => 'Delete Comments',
    ],

    'carts' => [
        'view' => 'View Carts',
        'send_reminder' => 'Send Cart Reminders',
    ],

    'roles' => [
        'view' => 'View Roles',
    ],

    'permissions' => [
        'view' => 'View Permissions',
    ],
];
