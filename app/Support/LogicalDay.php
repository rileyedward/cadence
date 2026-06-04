<?php

namespace App\Support;

use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Logical-day math (doc 07): each user has a timezone + day_start_time. A logical
 * day runs from day_start to day_start + 24h, and the compiler works in
 * minutes-from-day_start. "Now" is always derived server-side.
 */
class LogicalDay
{
    public static function dayStartMinutes(User $user): int
    {
        [$h, $m] = array_map('intval', explode(':', substr((string) $user->day_start_time, 0, 5)));

        return $h * 60 + $m;
    }

    public static function now(User $user, ?CarbonInterface $now = null): CarbonImmutable
    {
        return CarbonImmutable::instance($now ?? now())->setTimezone($user->timezone ?: 'UTC');
    }

    /**
     * The date that owns the current logical day. Before day_start, we still belong
     * to the previous calendar date's logical day.
     */
    public static function currentDate(User $user, ?CarbonInterface $now = null): CarbonImmutable
    {
        $local = self::now($user, $now);
        $minuteOfDay = $local->hour * 60 + $local->minute;

        return $minuteOfDay < self::dayStartMinutes($user)
            ? $local->subDay()->startOfDay()
            : $local->startOfDay();
    }

    /**
     * Current time as minutes-from-day_start (0 .. 1439).
     */
    public static function currentMinute(User $user, ?CarbonInterface $now = null): int
    {
        $local = self::now($user, $now);
        $minuteOfDay = $local->hour * 60 + $local->minute;
        $fromStart = $minuteOfDay - self::dayStartMinutes($user);

        return ($fromStart + 1440) % 1440;
    }
}
