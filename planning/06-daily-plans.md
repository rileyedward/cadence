# 06 — Daily Plans (Instantiation)

## Goal
Instantiate a DailyPlan from a template for a date, capture per-block decisions, and provide the
daily planning-session UX. This is the bridge between static structure (04/05) and the compiler (07).

## Instantiation

### Service: `DailyPlanFactory::instantiate(User $user, Carbon $date, ?Template $template)`
1. Resolve template via `Template::resolveForDate` (doc 04) unless one is passed.
2. Create `daily_plans` row (`status = draft`), unique per (user, date) — if one exists, load it.
3. For each block (ordered): create a `daily_plan_block` **snapshotting** name, start/end time,
   flexibility_mode (so later template edits don't mutate this day — see doc 02).
4. Pre-fill defaults: primary intent = highest-weight `block_default_intents`; copy block
   `context` into `context_tags`/social/mobility defaults; no activities yet.

### Controller / routes
`DailyPlanController`: `index` (calendar/list of plans), `show` (the plan + current schedule),
`store` (instantiate for a date), `update` (save block decisions), `generate` (→ compiler, doc 07),
`destroy`.
- `store` accepts `{ date, template_id? }`.
- `update` accepts the full block-decisions payload (batch upsert of `daily_plan_blocks` +
  `daily_plan_block_activities`).

## Per-block decisions (the planning session)
For each block the user sets:
- `intent_id` (primary) + optional `secondary_intent_id`
- `activities`: ordered list → `daily_plan_block_activities` with `estimated_minutes`
  (defaulting from activity, editable)
- `energy_level`, `focus_intensity`, `social_context`, `mobility_preference`
- per-day `constraints` overrides, `context_tags`
- optional time-window nudge (only meaningful for `soft`/`adaptive` blocks; `strict` locked)

## Frontend — planning session UX
- `DailyPlans/Index.vue` — month/week calendar; click a date → instantiate or open.
- `DailyPlans/Show.vue` — **mobile-first, responsive** (doc 18):
  - The unit is the **block decision card** (`Components/PlanBlockCard.vue`): intent picker
    (defaults surfaced first), secondary intent, activity picker (doc 05) with per-activity
    duration, energy/focus/social/mobility selectors, constraints disclosure.
  - **Mobile:** a **stepper / stacked flow** — one decision card at a time (swipe or Next/Back),
    with a collapsible **timeline preview drawer**.
  - **Desktop (≥`lg`):** promotes to **two-pane** — decision cards left, live preview right.
  - Live **timeline preview** via a debounced server `preview` call (doc 07; no client compiler)
    updates as decisions change, before the authoritative `generate`. Same component in both layouts.
- Sticky footer / action bar: **Generate Day** → calls `generate` (server compile), then shows
  authoritative schedule and flips status to `generated`/`active`.
- "Quick-fill from habits" button — optional, pre-fills intents/activities from the local
  intelligence layer (doc 10, e.g. your usual choices for each block); user reviews and edits.

## Status lifecycle (manual + on-load, no scheduler)
`draft` (instantiated, deciding) → `generated` (compiled at least once) → `active` (today, in
progress, check-ins happening, doc 09) → `done` (past/closed).
- **Instantiation is manual** (user opens/creates a date). No cron auto-creates days.
- **`done` flips lazily on load:** a small `PlanLifecycle` check, run when the user loads
  Today/Index, marks any plan whose logical day (doc 07: user `timezone` + `day_start`) has fully
  passed as `done`, and marks today's generated plan `active`. No background jobs in this build.
  (A scheduled job can replace this later without changing the model.)

## Empty / cold-start states
- **No template resolves for the date:** prompt the user to pick a template or build one — never
  error. Offer "plan a blank day" (instantiate with zero blocks).
- **Template has no blocks / block has no activities:** valid; the plan/compile handles it
  (duration = window; empty plan = empty schedule, doc 07).
- **No history yet:** "Quick-fill from habits" and intelligence suggestions are hidden/disabled
  until there's enough history (doc 10 cold-start), rather than showing empty or guessed data.

## Acceptance criteria
- Instantiating a date snapshots blocks; editing the source template afterward does not change
  the existing plan (Pest test).
- Decisions persist via `update` (intents, activities w/ order + minutes, all selectors).
- Re-instantiating an existing date returns the existing plan, not a duplicate.
- The preview reflects decision changes live (debounced server preview) before generate.
- Renders as a stepper on mobile and two-pane on desktop from the same components (doc 18).

## Depends on
- `04-templates-blocks.md`, `05-intents-activities.md`. (Preview pane needs `07`/`13`; gate behind
  those or stub the preview until the compiler exists.)
