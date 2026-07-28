<?php

return [
    'title' => 'Activity Log',
    'subtitle' => 'A read-only record of admin actions across the site.',

    'filter_user' => 'Staff Member',
    'filter_action' => 'Action',
    'filter_subject_type' => 'Item Type',
    'all_users' => 'All Staff',
    'all_actions' => 'All Actions',
    'all_subject_types' => 'All Item Types',
    'date_from' => 'From',
    'date_to' => 'To',
    'apply' => 'Apply',

    'column_timestamp' => 'When',
    'column_user' => 'Staff Member',
    'column_action' => 'Action',
    'column_subject' => 'Item Type',
    'column_description' => 'Details',

    'system' => 'System',
    'no_logs' => 'No activity matches this filter.',

    'actions' => [
        'created' => 'Created',
        'updated' => 'Updated',
        'deleted' => 'Deleted',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'published' => 'Published',
        'archived' => 'Archived',
        'duplicated' => 'Duplicated',
        'enabled' => 'Enabled',
        'disabled' => 'Disabled',
        'role_changed' => 'Role Changed',
        'permissions_changed' => 'Permissions Changed',
        'password_reset' => 'Password Reset',
        'force_logout' => 'Forced Logout',
    ],

    'subjects' => [
        'Product' => 'Product',
        'Category' => 'Category',
        'Order' => 'Order',
        'Coupon' => 'Coupon',
        'Review' => 'Review',
        'BlogPost' => 'Blog Post',
        'BlogComment' => 'Blog Comment',
        'Banner' => 'Banner',
        'Faq' => 'FAQ',
        'Service' => 'Service',
        'ShippingMethod' => 'Shipping Method',
        'User' => 'User',
        'Customer' => 'Customer',
    ],
];
