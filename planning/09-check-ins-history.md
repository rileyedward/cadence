# 09 — Check-ins & History

## Goal
Lightweight logging of what actually happened per block (started / completed / skipped + actual
times), storage of actual-vs-planned, and the history records that feed the intelligence layer
(doc 10) and comparison views (doc 12).

## Check-ins

### Model
`check_ins` (doc 02): belongs to a `daily_plan_block`; `status` ∈ started|completed|skipped;
`actual_start`, `actual_end` datetimes; `note`. A block may have multiple check-ins over a day
(e.g. start then complete); the latest defines current state. Helper `DailyPlanBlock::currentStatus()`.

### Controller / routes
`CheckInController`: `store` (POST `/plan-blocks/{block}/check-in` with status + optional times),
`update`, `destroy`. Authorize via the plan policy.
- `start` sets `actual_start = now` (default), status `started`.
- `complete` sets `actual_end = now`, status `completed`.
- `skip` sets status `skipped`.

### Interaction with runtime (doc 08)
A block with a `started` or `completed` check-in is **frozen** by the recompiler. Starting a block
typically triggers a "recompile rest of day" so downstream reflows from the real current time.

## Actual-vs-planned
- **Planned** = the block's `daily_plan_block` window + its current `schedule_event`.
- **Actual** = check-in `actual_start`/`actual_end`.
- Provide a read model `PlanBlockComparison` (DTO/resource): planned start/end/duration,
  actual start/end/duration, deltas, status. No new table — derived from check-ins + events.

## History
- A "day" is historical once `daily_plans.status = done` (set when the date passes or user closes).
- History queries read across a user's past `daily_plans` + their blocks/check-ins, e.g.
  `HistoryQuery::forRange(User, from, to)`. Keep these as query objects in `app/Queries/` so both
  intelligence (10) and views (12) reuse them.
- Examples surfaced: completion rate per intent, average actual vs planned duration per block
  category, energy-level frequency, most-skipped blocks.

## Frontend
- On `DailyPlans/Show.vue` (active day): each timeline event gets check-in controls
  (`Components/CheckInControls.vue`): Start / Complete / Skip, with optional time edit + note.
- A subtle "actual vs planned" overlay on the active timeline (ghost of planned vs solid actual).
- History lives in doc 12's views; this doc provides the data/queries they consume.

## Acceptance criteria
- Start/complete/skip persist with correct timestamps and status transitions.
- Started/completed blocks are frozen by the recompiler (verified with doc 08 test).
- `PlanBlockComparison` returns correct planned/actual/delta values (Pest test).
- `HistoryQuery::forRange` returns aggregates over multiple past plans.

## Depends on
- `06-daily-plans.md`, `08-runtime-adjustments.md`.
