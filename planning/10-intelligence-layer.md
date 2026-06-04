# 10 — Intelligence Layer

## Goal
Local, deterministic intelligence computed from the user's own plan + check-in history — no
external APIs, no LLM, no AI service of any kind. Provides energy-aware suggestions, habit
detection, pattern analysis, and optimization recommendations.

## Services (`app/Services/Intelligence/`)
All read via doc 09's `HistoryQuery` objects; pure-ish (DB read in, plain DTOs out).

### EnergyAdvisor
- Learns typical energy per block/time-of-day from history (e.g. mornings trend `high`,
  late-evening `low`).
- On the planning session (doc 06), suggests an `energy_level` and flags mismatches
  ("you usually run low here but selected deep focus").

### HabitDetector
- Detects recurring intent/activity choices per block/weekday (frequency over a rolling window).
- Surfaces "you do `coding` in Freedom 4/5 weekdays" → offer as a quick-fill default.

### PatternAnalyzer
- Aggregates completion rate, skip rate, and actual-vs-planned duration drift per block category /
  intent. Identifies chronically over/under-estimated blocks.

### Optimizer
- Recommendations from the above, e.g.: "Recovery actual duration is +35% over planned — extend
  its window"; "Activation is skipped on Fridays — consider adaptive mode"; "Insert rest: two deep
  blocks back-to-back on Tuesdays."
- Pure suggestions; never auto-applies. Each recommendation = `{ kind, message, target, action? }`
  where `action` is an optional pre-filled change the UI can one-click apply.

## How it surfaces
- Planning session (doc 06): inline energy/habit suggestions on block cards, a "quick-fill from
  habits" action, and a "Suggestions" panel of optimizer recommendations.
- History views (doc 12): pattern stats power the charts.

This is the **only** "smart" layer in the app and it is entirely local and deterministic — no
LLM, no external AI service, no network calls.

## Controller / routes
`InsightController@index` (per plan or per range) → returns suggestions/recommendations as Inertia
props or a JSON partial for async panels. Authorize per user.

## Cold start (no/low history)
- Each service requires a **minimum sample** (config: `intelligence.min_samples`, e.g. 3
  occurrences) before it emits a suggestion. Below that it returns nothing — the UI hides the
  suggestion affordance rather than guessing.
- With zero history, "Quick-fill from habits" (doc 06) falls back to a block's highest-weight
  default intents only; if none, it's hidden.

## Determinism & config
- Thresholds (rolling window size, drift %, frequency cutoff, `min_samples`) live in
  `config/cadence.php` under `intelligence`. No hidden constants.
- Given the same history, output is stable — unit-testable with seeded history fixtures.

## Acceptance criteria
- With a seeded multi-week history, each service returns correct aggregates/suggestions
  (Pest tests with fixtures).
- Below `min_samples`, services return empty and the UI hides suggestions (no guessing).
- Recommendations include an optional one-click `action` payload the UI can apply.
- No external network calls in this layer.

## Depends on
- `09-check-ins-history.md` (history queries), `06-daily-plans.md` (surfacing).
