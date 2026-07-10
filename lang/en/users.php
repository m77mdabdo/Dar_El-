<?php

return [
    'title' => 'Users',
    'add_user' => 'Add User',
    'edit_user' => 'Edit User',
    'back_to_users' => 'Back to Users',

    'name' => 'Name',
    'email' => 'Email',
    'phone' => 'Phone',
    'password' => 'Password',
    'confirm_password' => 'Confirm Password',
    'role' => 'Role',
    'status' => 'Status',
    'active' => 'Active',
    'inactive' => 'Inactive',
    'created_at' => 'Created',

    'role_super_admin' => 'Super Admin',
    'role_admin' => 'Admin',
    'role_employee' => 'Employee',
    'role_customer' => 'Customer',
    'all_roles' => 'All Roles',

    'search_placeholder' => 'Search by name or email…',

    'email_verified' => 'Mark email as verified',
    'send_welcome_email' => 'Send welcome email with login details',

    'permissions_title' => 'Permissions',
    'permissions_hint' => 'Only used for the Employee role — Admin and Super Admin always have full access.',
    'select_all' => 'Select All',
    'clear_all' => 'Clear',
    'search_permissions' => 'Search permissions…',
    'presets_title' => 'Quick Presets',
    'apply_preset' => 'Apply',

    'presets' => [
        'product_manager' => 'Product Manager',
        'order_manager' => 'Order Manager',
        'inventory_manager' => 'Inventory Manager',
        'content_manager' => 'Content Manager',
        'customer_support' => 'Customer Support',
        'marketing_manager' => 'Marketing Manager',
    ],

    'save_user' => 'Save User',
    'reset_password' => 'Reset Password',
    'force_logout' => 'Force Logout',
    'confirm_delete' => 'Delete this user? This cannot be undone.',
    'confirm_reset_password' => 'Send a password reset link to this user?',
    'confirm_force_logout' => 'Log this user out of all devices?',
    'confirm_toggle_active' => 'Change this user\'s active status?',

    'no_users' => 'No users found.',

    'created' => 'User created successfully.',
    'updated' => 'User updated successfully.',
    'deleted' => 'User deleted successfully.',
    'user_disabled' => 'User has been deactivated.',
    'user_enabled' => 'User has been activated.',
    'reset_link_sent' => 'A password reset link has been sent to this user.',
    'force_logout_done' => 'This user has been logged out of all devices.',

    'cannot_change_own_role' => 'You cannot change your own role.',
    'cannot_delete_last_super_admin' => 'You cannot delete the last remaining Super Admin.',

    'primary_super_admin_badge' => 'Primary Super Admin',
    'primary_super_admin_hint' => 'This is the system\'s primary Super Admin account. Its role, email, permissions, and active status are locked, and it cannot be deleted.',
    'cannot_change_primary_super_admin_role' => 'The primary Super Admin\'s role cannot be changed.',
    'cannot_change_primary_super_admin_email' => 'The primary Super Admin\'s email cannot be changed.',
];
