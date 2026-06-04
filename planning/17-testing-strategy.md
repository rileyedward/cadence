# 17 — Testing Strategy

## Goal
Define how every phase is verified: Pest for PHP, Vitest for TS components, and dedicated
**PHP compiler fixtures**. The compiler is PHP-only (doc 07), so there are no cross-language parity
tests in this build.

## PHP — Pest
- **Feature tests** per controller/action: auth + policy (cross-user 403), validation failures,
  happy-path persistence, Inertia prop shape.
- **Unit tests** for services: `TimelineCompiler`, `Recompiler`, intelligence services,
  `DailyPlanFactory`, `Template::resolveForDate`, `PlanBlockComparison`.
- Use factories + dedicated seeders/fixtures. SQLite in-memory for speed.

## Compiler test fixtures (PHP)
- Store canonical cases as JSON in `tests/fixtures/compiler/*.json`: each `{ input, config,
  expectedEvents }`, loaded by Pest unit tests.
- Required cases (one per doc-07 edge case):
  - **sample-day** — the spec's 8-block day (strict Work, adaptive Recovery, past-`day_start`
    Freedom→Wind Down). Asserts buffers, ordering, no strict movement (matches the doc-07 worked example).
  - **adaptive-overflow** — an adaptive block overruns → downstream reflow.
  - **strict-anchor** — overflow into a strict block compresses upstream, never moves strict; truncates at anchor.
  - **energy-rest** — two high+deep blocks adjacent → rest inserted (`restInserted`).
  - **runtime-freeze** — recompile with `fromMin` mid-day + a started check-in → past frozen.
  - **soft-tolerance** — soft block shifts forward within tolerance, clamps beyond.
  - **gaps / overlap / empty-plan** — gaps left as free time (no pull-earlier); overlap ordered + warned; empty plan → zero events.
  - **version-prune** — exceeding `max_schedule_versions` prunes oldest, keeps current.

## TS — Vitest (components only)
- Component tests (`@vue/test-utils`) for timeline rendering, planning preview reactivity, runtime
  drag/resize *interaction state* (not schedule computation — that's server-side), check-in controls.
  Cover **responsive layout** branches (stepper vs two-pane planning; bottom-nav vs sidebar) and the
  **touch fallback** (bottom-sheet controls equivalent to gestures).
- `types/enums.ts` value-parity test against an exported list of PHP enum values
  (`php artisan cadence:dump-enums` → json the test reads) so enums can't drift.
- *(Deferred: offline outbox tests — not in the first build, doc 18.)*

## Per-phase gates (mirror doc 16)
Each build-order gate maps to concrete tests; a phase isn't done until its tests are green:
- Phase 0: auth + policy + migrate/seed tests.
- Phase 1: template/fork/`resolveForDate`.
- Phase 2: instantiation snapshot + decision persistence.
- Phase 3: compiler fixtures (all edge cases) + version pruning.
- Phase 4 (**MVP gate**): freeze + runtime ops (server-computed) + `fromMin` skew validation;
  end-to-end loop verified with a human before continuing.
- Phase 5: intelligence fixtures — deterministic suggestions/recommendations, no external calls.
- Phase 6: rendering/aggregate tests + responsive/mobile layout branches.
- Phase 6b: Lighthouse PWA installability + offline-state UI.
- Phase 7: plugin idempotency + block-type defaults.
- Phase 8: calendar OAuth (mocked), idempotent push, import-anchor respected.

## Tooling
- `php artisan test` (Pest), `npm run test` (Vitest), `./vendor/bin/pint`, `npm run lint`.
- Mock external HTTP (Google Calendar only) — no live network in tests. The app has no AI/LLM
  dependency.

## End-to-end manual verification (per doc 16 DoD)
Seed sample template → instantiate today → set decisions → Generate → start/complete a check-in →
drag-adjust a future block (server confirms) → open History + actual-vs-planned. All steps work.

## Depends on
- Applies across all docs; pairs with `16-build-order.md`.
