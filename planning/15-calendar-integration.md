# 15 — Google Calendar Integration (Final Phase)

## Goal
The last feature: sync a generated daily schedule to the user's Google Calendar, with optional
import of existing calendar events as fixed/strict constraints. Build only after the core app is
complete and stable.

> Weather and other external integrations are intentionally **out of scope** for this project.

## Approach (interface-driven)
```php
interface CalendarSync {
    public function connectUrl(User $user): string;        // OAuth start
    public function handleCallback(User $user, Request $r): void;
    public function pushSchedule(Schedule $schedule): CalendarPushResult;
    public function importEvents(User $user, CarbonInterface $date): array; // busy blocks
    public function disconnect(User $user): void;
}
```
- **`GoogleCalendarDriver`** implements it via Google OAuth 2.0 + Calendar API
  (`google/apiclient`). Store tokens encrypted on a `calendar_connections` table
  (`user_id`, `provider`, `access_token`, `refresh_token`, `expires_at`, `calendar_id`).
- Config in `config/cadence.php` (`calendar.driver`, client id/secret/redirect from env).
- A **null/stub driver** when unconfigured so the app runs without Google credentials.

## Sync behavior
- **Push:** map each `block`/relevant `schedule_event` to a calendar event (title = label,
  description = intents/activities, time = event window). Tag events with a Cadence marker
  (extended property) so re-push updates/cleans prior Cadence events rather than duplicating.
  Buffers/transitions optional (config flag).
- **Import:** pull busy events for the date; surface them as fixed constraints the compiler treats
  like `strict` anchors (so planning reflows around real commitments). Imported items are
  read-only in Cadence.
- **Conflicts:** importing a busy slot overlapping a planned block flags it in the UI; user
  resolves (shrink/skip/move). Pushing never overwrites non-Cadence events.

## Controller / routes
`CalendarController`: `connect` (redirect to `connectUrl`), `callback`, `push` (POST for a
schedule), `import` (POST for a date), `disconnect`. Authorize per user; handle token refresh +
revocation gracefully.

## Frontend
- Settings page: connect/disconnect Google, choose target calendar, toggles (push buffers,
  auto-import busy).
- On a generated day: "Sync to Google Calendar" + an "imported busy" layer on the timeline.

## Acceptance criteria
- OAuth connect/callback stores encrypted tokens; disconnect revokes + clears them.
- Push creates/updates only Cadence-tagged events (idempotent; no duplicates on re-push).
- Import surfaces busy slots as strict-like anchors that the compiler respects.
- App fully functional with no calendar configured (stub driver).

## Depends on
- Core complete: `07`, `08`, `09`, `12`. Build last.
