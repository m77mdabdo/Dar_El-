<?php

namespace Tests\Unit\Support;

use App\Support\BusinessDay;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BusinessDayTest extends TestCase
{
    public function test_start_of_day_for_a_winter_date_is_two_hours_before_utc_midnight(): void
    {
        // Egypt observes standard time (UTC+2) in January (no DST).
        $start = BusinessDay::startOfDay('2026-01-15');

        $this->assertSame('2026-01-14 22:00:00', $start->toDateTimeString());
        $this->assertSame('UTC', $start->getTimezone()->getName());
    }

    public function test_end_of_day_for_a_winter_date_is_two_hours_before_the_next_utc_midnight(): void
    {
        $end = BusinessDay::endOfDay('2026-01-15');

        $this->assertSame('2026-01-15 21:59:59', $end->toDateTimeString());
    }

    public function test_start_of_day_for_a_summer_date_is_three_hours_before_utc_midnight_due_to_dst(): void
    {
        // Egypt observes DST (UTC+3) from April through October.
        $start = BusinessDay::startOfDay('2026-07-16');

        $this->assertSame('2026-07-15 21:00:00', $start->toDateTimeString());
    }

    public function test_today_range_reflects_the_current_cairo_calendar_day_not_utc(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 7, 16, 0, 30, 0, 'Africa/Cairo'));

        [$start, $end] = BusinessDay::todayRange();

        $this->assertSame('2026-07-15 21:00:00', $start->toDateTimeString());
        $this->assertSame('2026-07-16 20:59:59', $end->toDateTimeString());

        Carbon::setTestNow();
    }

    public function test_omitting_a_date_defaults_to_today_in_cairo(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 7, 16, 10, 0, 0, 'Africa/Cairo'));

        $start = BusinessDay::startOfDay();

        $this->assertSame('2026-07-15 21:00:00', $start->toDateTimeString());

        Carbon::setTestNow();
    }
}
