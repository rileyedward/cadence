# 12 — Visualization

## Goal
The views that render structure and execution: timeline view, block-based day view, weekly
overview, historical comparison, and actual-vs-planned analysis.

## Views & components

### Timeline view (`Components/TimelineBoard.vue`)
- Vertical time axis (handles past-midnight by extending past 24:00).
- Renders `schedule_events`: blocks colored by intent, buffers/transitions as thin gaps, rest
  flags marked. Used read-only in previews and interactively in runtime (doc 08: drag/resize,
  frozen past locked).
- Now-line indicator on the active day.

### Block-based day view (`Pages/DailyPlans/Show.vue` right pane / `Components/BlockDayView.vue`)
- Card-per-block layout (less time-literal, intent-first) — the "what state am I in" lens.
- Toggle between Timeline and Block views.

### Weekly overview (`Pages/Week/Index.vue`)
- 7-day grid; each day shows its resolved template + plan status + a compressed block strip.
- Quick navigation to instantiate/open a day.

### Historical comparison (`Pages/History/Index.vue`)
- Reads doc-09 `HistoryQuery` aggregates: completion rate by intent, skip rates, average
  actual-vs-planned drift by category, energy distribution.
- Simple charts (bars/heatmap). Use a lightweight chart approach (e.g. `unovis`/`chart.js` or
  hand-rolled SVG — pick in doc 13). Keep dependencies minimal.

### Actual-vs-planned analysis (`Components/PlanVsActual.vue`)
- Per-day overlay: ghost planned events vs solid actual (from check-ins). Delta badges
  (+/- minutes, late/early start). Consumes doc-09 `PlanBlockComparison`.

## Shared rendering concerns
- **Mobile-first (doc 18):** every view is designed for ~375px first — single vertical timeline
  with sticky now-line on mobile, wider/multi-column on desktop; weekly grid becomes a horizontal
  scroll/compact strip on phones; charts stack and scroll. Touch targets ≥44px.
- Single source for time→pixel mapping in `resources/js/lib/timeScale.ts` (reused by all
  time-based views) so timeline, runtime, and comparison align.
- Intent color/icon from the shared intent library prop (design tokens, doc 18).
- All components typed against `resources/js/types` (doc 13).
- **Empty states are first-class:** no plan for the date → call-to-action to plan; no history →
  friendly "not enough data yet" instead of blank charts; no template → prompt to create one.

## Acceptance criteria
- Timeline renders the sample day correctly incl. buffers, rest flags, and past-midnight blocks.
- Toggle between timeline and block-day views works.
- Weekly overview resolves and displays each day's template + status.
- History view shows correct aggregates from seeded history; actual-vs-planned overlay matches
  `PlanBlockComparison`.
- All views usable one-handed at ~375px width (doc 18).

## Depends on
- `07-timeline-compiler.md`, `08-runtime-adjustments.md`, `09-check-ins-history.md`,
  `13-frontend-architecture.md`, `18-design-system.md`.
