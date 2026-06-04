# 01 — Architecture

## Goal
Define the system architecture, the PHP-only server-authoritative compiler, the Inertia request
lifecycle, and the canonical directory layout. Every later doc places its code according to this map.

## High-level shape

```
Browser (Vue 3 + TS + Inertia)
   │  Inertia visits / form posts (no REST client needed)
   ▼
Laravel routes ─► Controllers ─► Form Requests (validate) ─► Policies (authorize)
                      │
                      ├─► Services (TimelineCompiler, intelligence)
                      │
                      └─► Eloquent models ─► SQLite
                      ▼
              API Resources ─► Inertia::render(props)
```

## The four layers → code

| Layer | Primary code |
|-------|--------------|
| Template (static) | `Template`, `Block`, `TemplateAssignment` models + `TemplateController`, `BlockController` |
| Instantiation (daily) | `DailyPlan`, `DailyPlanBlock` models + `DailyPlanController` |
| Compiler (generation) | `app/Services/Compiler/TimelineCompiler.php` (+ value objects) |
| Runtime (live edits) | `app/Services/Compiler/Recompiler.php` + `RuntimeController` |

## Server-authoritative compiler (PHP only)

- **Single source of truth = PHP.** `TimelineCompiler` produces and persists `Schedule` +
  `schedule_events`. Any save / generate / runtime op recompiles on the server and returns the
  authoritative schedule via Inertia.
- **No client-side compiler.** The browser shows a *cosmetic* optimistic transform during a drag
  (move the touched event + shift the next ones by the same delta), never a full reflow (doc 07
  "Client preview"). A full TS mirror was deliberately **deferred** to avoid two-engine drift.

### Flow of a runtime drag
1. User drags a block → Vue applies the naive optimistic transform → UI updates instantly.
2. Vue posts the runtime op (Inertia) → server `Recompiler` recomputes authoritatively.
3. Server returns the new `Schedule`; client replaces the optimistic state. **Server always wins.**

## Inertia request lifecycle
- Pages are `resources/js/Pages/**`. Controllers call `Inertia::render('Page', $props)`.
- Mutations are Inertia `router.post/put/delete`; controllers redirect back with fresh props.
- Shared data (auth user, flash, intent library) via `HandleInertiaRequests` middleware.
- No separate REST API for the SPA. (A thin token API may be added later for plugins/integrations
  — see docs 14/15 — but the app itself uses Inertia.)

## Directory layout

```
app/
  Models/                 Template, Block, TemplateAssignment, Intent, Activity,
                          DailyPlan, DailyPlanBlock, Schedule, ScheduleEvent, CheckIn
  Http/
    Controllers/          TemplateController, BlockController, IntentController,
                          ActivityController, DailyPlanController, ScheduleController,
                          RuntimeController, CheckInController, InsightController, ...
    Requests/             *Request form requests per action
    Resources/            *Resource API resources
    Middleware/           HandleInertiaRequests
  Policies/               one per user-owned model
  Services/
    Compiler/             TimelineCompiler, Recompiler, value objects (BlockInput, EventDraft)
    Intelligence/         EnergyAdvisor, HabitDetector, PatternAnalyzer, Optimizer
    Calendar/             CalendarSync (interface), GoogleCalendarDriver   (doc 15)
  Plugins/                IntentModule contract + registry                  (doc 14)
config/
  cadence.php           buffers, min-rest, energy rules, intelligence + calendar config
resources/js/
  Pages/                  Inertia pages
  Components/             shared Vue components (timeline, block card, pickers)
  stores/                 Pinia stores (runtime optimistic-transform state)
  types/                  shared TS types/enums mirroring backend
  lib/                    utils (time math, etc.)
tests/
  Feature/  Unit/         Pest tests
resources/js/**/__tests__  Vitest component tests
```

## Config-driven rules
`config/cadence.php` holds tunables the compiler/intelligence read (default buffer minutes,
minimum rest minutes, energy adjacency rules, intelligence thresholds, calendar settings).
No magic numbers inside services.

## Acceptance criteria
- A reader can place any new class/file in the correct directory from this map.
- The compiler's source-of-truth (PHP) vs preview (TS) responsibilities are unambiguous.
- The runtime drag flow (optimistic → server-authoritative) is clear.

## Depends on
- `00-overview.md`
