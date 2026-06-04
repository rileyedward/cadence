# 08 — Runtime Adjustments

## Goal
Let the user modify the day in real time and reflow the remainder while preserving what's already
happened. Instant cosmetic optimistic transform on the client; server-authoritative recompile via PHP.

## Operations
- **Extend / shorten** an event (change duration).
- **Swap activity** within a block (changes estimate → may change duration).
- **Skip block** (remove from remaining schedule).
- **Merge blocks** (combine two adjacent into one event range).
- **Split block** (divide one into two with a chosen split point).
- **Drag reschedule** (move a block's start; respects `strict` anchors).
- **Recompile remaining** (re-run reflow for everything after `now`).

## Recompiler — `Recompiler::recompile(Schedule, fromMin, ops[]): Schedule`
Builds on doc 07's `compile` but **freezes the past**:
1. Determine `fromMin` = current time-of-day in minutes (passed by client; server validates).
2. **Freeze** every event that ends at/before `fromMin`, plus any block whose `daily_plan_block`
   has a `check_in` of status `started` or `completed` (doc 09). Frozen events are copied
   verbatim into the new schedule.
3. Apply the requested `ops` to the *remaining* (unfrozen) block inputs (skip removes; merge/split
   restructure; extend/shorten/swap change durations; drag sets a new start hint).
4. Run the doc-07 placement/reflow/buffer/rest logic over the remaining blocks only, with the
   cursor starting at `max(fromMin, lastFrozenEnd)`.
5. Persist as a new `Schedule` version (`is_current=true`), pruned per `max_schedule_versions` (doc 07).

`strict` blocks remain anchors even during runtime; a strict block already in progress is frozen.
`fromMin` is computed by the server from now-in-user-tz minus `day_start` (doc 07); a client-sent
value is validated against server time (±skew) and clamped — clients can't freeze/unfreeze by lying.

## Flow (optimistic → authoritative)
1. User performs an op → Vue applies a **naive optimistic transform** (move/resize the touched
   event and shift only the immediately-following events by the same delta) for visual smoothness.
   No full reflow on the client (doc 07 "Client preview").
2. Vue posts the op to `RuntimeController` (Inertia) with the op payload (server derives `fromMin`).
3. Server `Recompiler` produces the authoritative schedule and returns it.
4. Client replaces the optimistic events with the server result. **Server always wins** — the
   cosmetic transform is discarded.

## Controller / routes
`RuntimeController` under a plan/schedule:
`POST /plans/{plan}/runtime/{op}` where op ∈ extend|shorten|swap|skip|merge|split|drag|recompile.
Each has a form request validating its payload (`event_id`, `delta`, `activity_id`, `at_min`, etc.).
Authorize via the plan's policy.

## Frontend
- Interactive timeline (`Components/TimelineBoard.vue`, doc 12) — **mobile-first, touch-native**
  (doc 18). Long-press to grab + drag a future block; drag the top/bottom handle to resize; light
  haptics on grab/drop. One `useDragResize` composable (Pointer Events) covers touch + mouse.
- Tapping a block opens a **bottom sheet** with explicit controls (extend/shorten/move-to-time/
  swap/skip/merge/split) — the accessible, no-drag fallback for every gesture.
- Past portion of the day is visually locked (frozen); only future events are editable.
- A "Recompile rest of day" action (floating on mobile) triggers the recompile op explicitly.
- Pinia store `stores/runtime.ts` holds the optimistic transform + in-flight/reconciled status.
- **Offline:** deferred (doc 18 — installable shell only in the first build). For now, runtime ops
  require connectivity; show a clear offline state.

## Acceptance criteria
- Each op produces a correct new schedule version (server-computed); old versions pruned per config.
- Frozen (past / started / completed) events are never moved by a recompile (Pest test).
- Skipping/merging/splitting reflow downstream correctly and respect `strict` anchors.
- The optimistic transform is always replaced by the authoritative server schedule (server wins);
  a client-sent `fromMin` outside the allowed skew is rejected/clamped.

## Depends on
- `07-timeline-compiler.md`, `09-check-ins-history.md` (for freeze-on-checkin), `13` + `18`
  (interaction, touch model).
