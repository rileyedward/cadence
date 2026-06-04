<?php

namespace App\Services\Calendar;

use App\Models\Schedule;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;

/**
 * Calendar sync contract (doc 15). The NullCalendarDriver implements this when no
 * provider is configured so the whole app runs without Google credentials.
 */
interface CalendarSync
{
    /** Whether a real provider is configured (env credentials present). */
    public function isConfigured(): bool;

    public function connectUrl(User $user): string;

    public function handleCallback(User $user, Request $request): void;

    public function pushSchedule(Schedule $schedule): CalendarPushResult;

    /**
     * Busy events for a date, normalized to anchors the compiler respects.
     *
     * @return array<int, array{source_uid:string, title:?string, start_time:string, end_time:string}>
     */
    public function importEvents(User $user, CarbonInterface $date): array;

    public function disconnect(User $user): void;
}
