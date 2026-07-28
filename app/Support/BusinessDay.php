<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * "Today"/"this day" per the business's own calendar (Cairo), not the
 * app's storage timezone (config('app.timezone') stays UTC deliberately —
 * see the 2026-07 timezone audit: flipping that config globally would
 * silently reinterpret every existing historical timestamp by a 2-3 hour
 * offset, since Eloquent parses stored datetime strings using whatever
 * PHP's default timezone currently is). Every day-boundary query
 * (Reports date ranges, Dashboard/admin "today" stats) should go through
 * here instead of the raw today()/whereDate(..., today()) pattern, so a
 * Cairo calendar day lines up with real Cairo midnight rather than UTC
 * midnight — otherwise orders placed in the first ~2-3 hours of a Cairo
 * day get silently counted under the previous day's report instead.
 *
 * Uses the real 'Africa/Cairo' IANA timezone (not a hardcoded offset)
 * because Egypt observes DST (UTC+2 in winter, UTC+3 in summer) — a fixed
 * offset would be wrong for roughly half the year.
 */
class BusinessDay
{
    public const TIMEZONE = 'Africa/Cairo';

    public static function now(): Carbon
    {
        return Carbon::now(self::TIMEZONE);
    }

    protected static function resolveDay(?string $date): Carbon
    {
        return $date ? Carbon::parse($date, self::TIMEZONE) : self::now();
    }

    /**
     * Start of the given Cairo calendar day ("Y-m-d", e.g. from an admin
     * date-picker) — or today, if omitted — returned in UTC, ready to
     * compare directly against a UTC-stored created_at column.
     */
    public static function startOfDay(?string $date = null): Carbon
    {
        return self::resolveDay($date)->copy()->startOfDay()->utc();
    }

    public static function endOfDay(?string $date = null): Carbon
    {
        return self::resolveDay($date)->copy()->endOfDay()->utc();
    }

    /**
     * @return array{0: Carbon, 1: Carbon} start-of-day, end-of-day (UTC)
     */
    public static function todayRange(): array
    {
        return [self::startOfDay(), self::endOfDay()];
    }
}
