<?php

namespace App\Services\Calendar;

use App\Models\Schedule;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Default driver when no calendar provider is configured. Keeps the app fully
 * functional with no Google credentials (doc 15 acceptance).
 */
class NullCalendarDriver implements CalendarSync
{
    public function isConfigured(): bool
    {
        return false;
    }

    public function connectUrl(User $user): string
    {
        throw new RuntimeException('No calendar provider is configured.');
    }

    public function handleCallback(User $user, Request $request): void
    {
        // no-op
    }

    public function pushSchedule(Schedule $schedule): CalendarPushResult
    {
        return new CalendarPushResult(skipped: true);
    }

    public function importEvents(User $user, CarbonInterface $date): array
    {
        return [];
    }

    public function disconnect(User $user): void
    {
        // no-op
    }
}
