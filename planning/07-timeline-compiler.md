# 07 — Timeline Compiler (The Engine)

## Goal
A deterministic engine that turns a `DailyPlan` into a `Schedule` of ordered `schedule_events`.
**The compiler lives only in PHP and is the single source of truth.** The client never recomputes
the full schedule; it shows a lightweight optimistic preview (see "Client preview" below) and the
server returns the authoritative result. (A full TS mirror was considered and **deferred** — the
two-engine parity cost isn't worth it for the first build.)

## Time & day boundary (read first)
All scheduling is per-user-timezone and organized around a **logical day**, not the calendar day:
- Each user has `timezone` (IANA) and `day_start_time` (default 04:00) — doc 02.
- A logical day runs from `day_start_time` to `day_start_time + 24h`. The compiler works in
  **minutes-from-`day_start`** integers (0 … 1440+), so a block at 01:30 that falls *after* a
  20:00 block is simply a larger minute value — no special "past midnight" casing beyond this.
- The owning `daily_plans.date` is the logical day's start date. Check-in datetimes (doc 09) are
  stored in UTC but interpreted against the user's timezone + day boundary.
- Anything reading "now" (the runtime recompiler, doc 08; the on-load `done` flip, doc 06)
  computes current-minute = now-in-user-tz minus `day_start`. The server is authoritative for
  "now"; a client-supplied `fromMin` is validated against server time (±a small skew) and clamped.

## Inputs / outputs
- **Input:** a normalized list of block inputs (one per `daily_plan_block`), each:
  `{ id, label, startMin, endMin, mode, energy, focus, priority, constraints, activityMinutes[] }`
  where times are **minutes-from-`day_start` integers** (see "Time & day boundary") and
  `activityMinutes` are the per-activity estimates. Plus global config (below).
- **Output:** ordered `EventDraft[]`: `{ type, label, startMin, endMin, sourceBlockId?, metadata }`,
  persisted as a new `Schedule` (version++) + `schedule_events`, with `is_current=true` (previous
  current set false).

## Config (`config/cadence.php`)
```
buffer_minutes        default gap inserted between consecutive blocks (e.g. 5)
min_rest_minutes      minimum rest enforced after high-intensity blocks (e.g. 15)
soft_tolerance_minutes how far a soft block may shift (e.g. 30)
energy_rules          adjacency rules, e.g. no two (high + deep) blocks without rest between
max_schedule_versions retain only the current + last N versions per plan (e.g. 10); prune rest
```
Times are minutes-from-`day_start` (doc "Time & day boundary"). A block whose clock time is
before `day_start` (e.g. 01:30 with a 04:00 boundary belonging to the *previous* logical day's
evening) gets `+1440`, so ordering stays monotonic without special-casing midnight.

## Algorithm — `TimelineCompiler::compile(DailyPlan): Schedule`
Pure function (no side effects beyond the final persist). Steps:

1. **Normalize & order.** Build block inputs, ordering by `startMin`, then `priority`,
   then `order`. Apply `block_dependencies` as a stable tie-break (`after` pushes later).
2. **Estimate durations.** `estimated = sum(activityMinutes)`. Clamp to
   `[constraints.minDuration ?? 0, constraints.maxDuration ?? windowLength]` and to the block
   window length. If no activities, duration = window length.
3. **Place by flexibility mode**, walking left→right with a `cursor`:
   - `strict`: fixed to its window. Never moved. Acts as a hard anchor.
   - `soft`: starts at its window start but may shift up to `soft_tolerance_minutes` later if the
     cursor (from upstream overflow) demands; clamps within tolerance.
   - `adaptive`: starts at `max(cursor, windowStart)`; freely reflows; compressed first when space
     is short.
4. **Buffers/transitions.** Insert a `buffer` (or `transition` when context/location changes,
   e.g. mobility solo→commute) of `buffer_minutes` between consecutive block events when the gap
   is smaller than the buffer; never overlap a `strict` anchor.
5. **Conflict resolution / reflow.** When a block's placed end exceeds the next block's required
   start:
   - If next is `strict`: compress the current block toward its `minDuration`; if still
     overflowing, flag `metadata.overflow=true` (do not move the strict anchor).
   - If next is `soft`: shift it within tolerance; beyond tolerance, compress `adaptive`
     neighbors first, then flag.
   - If next is `adaptive`: push/compress it as needed.
   - Compression order when reclaiming time: `adaptive` → `soft` → never `strict`.
6. **Minimum rest + energy consistency.** After a block with `energy=high` AND `focus=deep`,
   ensure at least `min_rest_minutes` before the next high/deep block; if absent, insert a
   `buffer` event with `metadata.restInserted=true` (reflowing downstream `adaptive`/`soft`).
   Apply any `constraints.minRestAfter`.
7. **Emit.** Produce ordered `EventDraft[]` with sequential `order`, carrying intents/energy/flags
   in `metadata`. Persist as a versioned `Schedule`.

Determinism rules: integer minute math only; stable sort with explicit tie-breakers; no clock/now
reads inside `compile` (current time is only used by the runtime recompiler, doc 08).

## Edge cases (must be deterministic)
- **Gaps between blocks** (non-contiguous templates): allowed. The gap is left as free time; the
  compiler does **not** pull later blocks earlier to fill gaps in the default pass — it only ever
  pushes/compresses forward when there's overflow. (Forward-only keeps it predictable.)
- **`soft` shifts forward only.** A soft block may move *later* within `soft_tolerance_minutes`; it
  never moves earlier than its window start.
- **Overflow that can't be absorbed:** if compressing every downstream `adaptive`/`soft` to its
  `minDuration` still doesn't fit before the next `strict` anchor (or day end), place what fits,
  set `metadata.overflow=true` on the offending block, and **truncate at the anchor** — never move
  or shorten a `strict` block, never run past `day_start+1440`.
- **Overlapping template windows:** blocks are still ordered by `startMin`; treat as back-to-back
  and let buffer/reflow resolve. Overlap is a planning smell, surfaced as a warning, not an error.
- **Activities shorter than the window:** duration = sum(activityMinutes); remaining window time is
  free space inside the block (no filler event). Activities exceeding the window clamp to
  `maxDuration`/window and flag `metadata.overran=true`.
- **Empty plan / no blocks:** produce an empty schedule (valid, zero events).

## Worked example (the spec's sample day, `day_start`=04:00)
Input blocks (minutes-from-04:00): Morning 180–240, Commute Prep 240–270 (strict), Work 270–720
(strict), Commute Home 720–750 (strict), Recovery 750–840 (adaptive), Activation 840–960 (soft),
Freedom 960–1290 (soft/adaptive), Wind Down 1290–1320 (adaptive). Suppose Recovery's activities
sum to 120 min (overruns its 90-min window by 30):
- Strict anchors (Commute Prep, Work, Commute Home) never move.
- Recovery starts at 750, wants to end at 870 (120 min) but Activation (soft) starts at 840 →
  Activation shifts later within tolerance (≤30) to 870; Recovery placed 750–870.
- Freedom (soft/adaptive) and Wind Down (adaptive) reflow forward to absorb the 30-min push;
  Freedom compresses first. Buffers inserted between non-adjacent transitions.
- Result: an ordered event list, Work untouched, day still ends by 1320. (Encode this as a fixture
  in doc 17.)

## Value objects
`app/Services/Compiler/`: `BlockInput`, `EventDraft`, `CompileConfig`, `CompileResult`.
Keep `compile` free of Eloquent — pass plain data in, persist outside (a thin
`ScheduleWriter` maps `EventDraft[]` → rows). This makes it unit-testable and keeps the door open
to a future TS port without rework.

## Persistence & version retention
`ScheduleWriter` writes a new `Schedule` (version++, `is_current=true`, prior current → false) +
its `schedule_events`. To avoid version bloat from frequent recompiles/drags, prune to
`config('cadence.max_schedule_versions')` most-recent versions per plan (always keep the current
one). Pruning runs after each write.

## Client preview (no TS compiler)
The browser does **not** recompute the schedule. For instant feedback:
- **Planning (doc 06):** editing decisions debounces a call to `generate` (or a cheaper
  `POST .../preview` that compiles without persisting) and swaps in the server result. Optional
  ultra-light hint only (e.g. "durations exceed window") computed client-side; not a full reflow.
- **Runtime (doc 08):** a drag/resize applies a naive optimistic transform (move/resize the dragged
  event and shift only the immediately-following events by the same delta) purely for visual
  smoothness, then the server `Recompiler` returns the authoritative schedule which replaces it.
  The optimistic transform is cosmetic and intentionally simple — server always wins.

## Controller
`ScheduleController@generate` (called from doc 06's `generate`): builds inputs from the plan,
runs `TimelineCompiler`, persists, returns the current schedule via Inertia.
`ScheduleController@preview` (optional): compiles + returns without persisting, for live planning.

## Acceptance criteria
- Pure `compile` produces stable, ordered events; given the spec's sample day it yields a sane
  timeline with buffers and no `strict` (Work) movement.
- Overflowing an adaptive block reflows downstream; a strict block never moves.
- High+deep adjacency inserts rest (`restInserted` flag).
- Past-`day_start` blocks (Freedom→Wind Down) compile correctly via the +1440 rule.
- All edge cases above behave deterministically (each has a fixture in doc 17).
- Old schedule versions are pruned to `max_schedule_versions`; current is always retained.

## Depends on
- `06-daily-plans.md`, `02-data-model.md` (timezone + day_start fields).
