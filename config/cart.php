<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Abandoned Cart Tracking & Reminders
    |--------------------------------------------------------------------------
    */

    'abandoned_after_minutes' => env('CART_ABANDONED_AFTER_MINUTES', 120),

    'reminder_interval_hours' => env('CART_REMINDER_INTERVAL_HOURS', 2),

    'max_reminders' => env('CART_MAX_REMINDERS', 3),

    'high_value_threshold' => env('CART_HIGH_VALUE_THRESHOLD', 2000),

    'expires_after_days' => env('CART_EXPIRES_AFTER_DAYS', 30),

];
