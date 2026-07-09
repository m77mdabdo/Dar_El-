<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Single source of truth for the abandoned-cart-reminder knobs the admin
 * can override at runtime via Settings — each getter falls back to
 * config('cart.*') when the setting row doesn't exist yet, so nothing
 * breaks on a fresh install before anyone has visited the settings page.
 */
class CartReminderConfig
{
    public static function enabled(): bool
    {
        return Setting::get('cart_reminders_enabled', '1') === '1';
    }

    public static function notificationEnabled(): bool
    {
        return Setting::get('cart_reminder_notification_enabled', '1') === '1';
    }

    /**
     * How long a cart must sit inactive before it's flipped to "abandoned"
     * and becomes eligible for its first reminder. Stored as whole hours in
     * Settings (admin-facing), converted to minutes for the underlying
     * config('cart.abandoned_after_minutes') semantics.
     */
    public static function firstDelayMinutes(): int
    {
        $defaultHours = (int) round(config('cart.abandoned_after_minutes') / 60);

        return (int) Setting::get('cart_reminder_first_delay_hours', (string) $defaultHours) * 60;
    }

    public static function intervalHours(): int
    {
        return (int) Setting::get('cart_reminder_interval_hours', (string) config('cart.reminder_interval_hours'));
    }

    public static function maxReminders(): int
    {
        return (int) Setting::get('cart_max_reminders', (string) config('cart.max_reminders'));
    }
}
