<?php

return [
    /*
     * The one account that must always exist, always hold the super_admin
     * role, and always have every permission. Enforced by
     * PrimarySuperAdminSeeder (runs on every deploy/seed) and by
     * User::isPrimarySuperAdmin(), which UserController/UpdateUserRequest
     * use to refuse role changes, permission removal, deletion, and
     * disabling of this specific account — by anyone, including other
     * Super Admins.
     */
    'email' => env('PRIMARY_SUPER_ADMIN_EMAIL', 'creativedigitalmohamedabdo@gmail.com'),
];
