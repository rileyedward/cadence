<?php

namespace App\Services\Calendar;

use App\Models\CalendarConnection;
use App\Models\Schedule;
use App\Models\User;
use App\Support\LogicalDay;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendar;
use Google\Service\Calendar\Event as GoogleEvent;
use Google\Service\Calendar\EventDateTime;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Google Calendar implementation (doc 15). Push is idempotent via a Cadence
 * marker extended property; import surfaces busy events as strict-like anchors.
 */
class GoogleCalendarDriver implements CalendarSync
{
    private const MARKER = 'cadence';

    public function isConfigured(): bool
    {
        $cfg = config('cadence.calendar.google');

        return ! empty($cfg['client_id']) && ! empty($cfg['client_secret']) && ! empty($cfg['redirect']);
    }

    public function connectUrl(User $user): string
    {
        $client = $this->baseClient();
        $client->setScopes([GoogleCalendar::CALENDAR_EVENTS, GoogleCalendar::CALENDAR_READONLY]);
        $client->setState((string) $user->id);

        return $client->createAuthUrl();
    }

    public function handleCallback(User $user, Request $request): void
    {
        $client = $this->baseClient();
        $token = $client->fetchAccessTokenWithAuthCode((string) $request->query('code'));

        if (isset($token['error'])) {
            throw new RuntimeException('Google authorization failed: '.$token['error']);
        }

        CalendarConnection::updateOrCreate(
            ['user_id' => $user->id, 'provider' => 'google'],
            [
                'access_token' => $token['access_token'] ?? null,
                'refresh_token' => $token['refresh_token'] ?? null,
                'expires_at' => isset($token['expires_in']) ? now()->addSeconds((int) $token['expires_in']) : null,
                'calendar_id' => 'primary',
            ],
        );
    }

    public function pushSchedule(Schedule $schedule): CalendarPushResult
    {
        $plan = $schedule->dailyPlan;
        $plan->loadMissing('user');
        $service = $this->service($plan->user);
        $calendarId = $this->connection($plan->user)->calendar_id;
        $tz = $plan->user->timezone ?: 'UTC';
        $dayStart = LogicalDay::dayStartMinutes($plan->user);

        // Idempotent: clear prior Cadence events for the day, then re-insert.
        $deleted = $this->clearCadenceEvents($service, $calendarId, $plan->date, $tz);

        $created = 0;
        $pushBuffers = (bool) config('cadence.calendar.push_buffers', false);

        foreach ($schedule->events()->orderBy('order')->get() as $event) {
            if ($event->type->value !== 'block' && ! $pushBuffers) {
                continue;
            }

            $startMin = (int) ($event->metadata['start_min'] ?? 0);
            $endMin = (int) ($event->metadata['end_min'] ?? 0);

            $gEvent = new GoogleEvent([
                'summary' => $event->label,
                'description' => 'Synced from Cadence',
                'start' => $this->dateTime($plan->date, $startMin, $tz, $dayStart),
                'end' => $this->dateTime($plan->date, $endMin, $tz, $dayStart),
                'extendedProperties' => ['private' => [self::MARKER => '1', 'cadence_event' => (string) $event->id]],
            ]);

            $service->events->insert($calendarId, $gEvent);
            $created++;
        }

        return new CalendarPushResult(created: $created, deleted: $deleted);
    }

    public function importEvents(User $user, CarbonInterface $date): array
    {
        $service = $this->service($user);
        $calendarId = $this->connection($user)->calendar_id;
        $tz = $user->timezone ?: 'UTC';

        $start = CarbonImmutable::parse($date->toDateString(), $tz)->startOfDay();
        $end = $start->addDay();

        $events = $service->events->listEvents($calendarId, [
            'timeMin' => $start->toRfc3339String(),
            'timeMax' => $end->toRfc3339String(),
            'singleEvents' => true,
            'orderBy' => 'startTime',
        ]);

        $busy = [];
        foreach ($events->getItems() as $item) {
            // Skip our own pushed events and all-day events.
            if (($item->getExtendedProperties()?->getPrivate()[self::MARKER] ?? null) === '1') {
                continue;
            }
            $startDt = $item->getStart()?->getDateTime();
            $endDt = $item->getEnd()?->getDateTime();
            if (! $startDt || ! $endDt) {
                continue;
            }

            $busy[] = [
                'source_uid' => $item->getId(),
                'title' => $item->getSummary(),
                'start_time' => CarbonImmutable::parse($startDt)->setTimezone($tz)->format('H:i'),
                'end_time' => CarbonImmutable::parse($endDt)->setTimezone($tz)->format('H:i'),
            ];
        }

        return $busy;
    }

    public function disconnect(User $user): void
    {
        $connection = CalendarConnection::where('user_id', $user->id)->where('provider', 'google')->first();
        if (! $connection) {
            return;
        }

        try {
            $client = $this->baseClient();
            if ($connection->access_token) {
                $client->revokeToken($connection->access_token);
            }
        } catch (\Throwable) {
            // Revocation failures shouldn't block local disconnect.
        }

        $connection->delete();
    }

    private function baseClient(): GoogleClient
    {
        $cfg = config('cadence.calendar.google');
        $client = new GoogleClient;
        $client->setClientId($cfg['client_id']);
        $client->setClientSecret($cfg['client_secret']);
        $client->setRedirectUri($cfg['redirect']);
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        return $client;
    }

    private function connection(User $user): CalendarConnection
    {
        return CalendarConnection::where('user_id', $user->id)->where('provider', 'google')->firstOrFail();
    }

    private function service(User $user): GoogleCalendar
    {
        $connection = $this->connection($user);
        $client = $this->baseClient();
        $client->setAccessToken(array_filter([
            'access_token' => $connection->access_token,
            'refresh_token' => $connection->refresh_token,
        ]));

        if ($client->isAccessTokenExpired() && $connection->refresh_token) {
            $token = $client->fetchAccessTokenWithRefreshToken($connection->refresh_token);
            $connection->update([
                'access_token' => $token['access_token'] ?? $connection->access_token,
                'expires_at' => isset($token['expires_in']) ? now()->addSeconds((int) $token['expires_in']) : null,
            ]);
        }

        return new GoogleCalendar($client);
    }

    private function clearCadenceEvents(GoogleCalendar $service, string $calendarId, CarbonInterface $date, string $tz): int
    {
        $start = CarbonImmutable::parse($date->toDateString(), $tz)->startOfDay();
        $existing = $service->events->listEvents($calendarId, [
            'timeMin' => $start->toRfc3339String(),
            'timeMax' => $start->addDay()->toRfc3339String(),
            'privateExtendedProperty' => self::MARKER.'=1',
            'singleEvents' => true,
        ]);

        $deleted = 0;
        foreach ($existing->getItems() as $item) {
            $service->events->delete($calendarId, $item->getId());
            $deleted++;
        }

        return $deleted;
    }

    /**
     * Convert minutes-from-day_start to an RFC3339 EventDateTime on the plan date.
     */
    private function dateTime(CarbonInterface $date, int $minutesFromDayStart, string $tz, int $dayStart): EventDateTime
    {
        $base = CarbonImmutable::parse($date->toDateString(), $tz)
            ->startOfDay()
            ->addMinutes($dayStart + $minutesFromDayStart);

        return new EventDateTime(['dateTime' => $base->toRfc3339String(), 'timeZone' => $tz]);
    }
}
