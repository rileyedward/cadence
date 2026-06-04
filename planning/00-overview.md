# 00 — Overview

> **For the building agent:** Read this file first, then `16-build-order.md`. Build in the
> numbered order. Every doc ends with **Acceptance criteria** and **Depends on** — do not start
> a doc until its dependencies pass their acceptance criteria.

## What Cadence is

Cadence is a **life-structure compiler**: it converts flexible human intention into an
adaptive daily timeline. Users do not schedule individual tasks. Instead they:

1. Define **routine templates** — recurring weekly structure made of time **blocks**.
2. Each day, **instantiate** a template and choose, per block, *what state they'll be in*
   (an **intent**) plus optional **activities**, energy, focus, social, and mobility settings.
3. The system **compiles** those decisions into a concrete, ordered **timeline** with buffers
   and transitions, and lets the user **adjust it live** while it reflows the rest of the day.

It is **not** a task manager, a rigid calendar, or a habit tracker. The guiding question is
*"What state am I in during this block today?"* rather than *"What task must I do at this time?"*

It ships as a **mobile-first, installable PWA** (install to home screen, app-like shell). Full
offline use of the live day is **deferred** to a later phase — see `18`.

## Core concepts (glossary)

| Term                    | Meaning                                                                                                                   |
| ----------------------- | ------------------------------------------------------------------------------------------------------------------------- |
| **Template**            | A reusable weekly structure owned by a user. Contains ordered blocks. Forkable.                                           |
| **Template assignment** | Rule mapping a template to days (weekday/weekend/custom/date-range).                                                      |
| **Block**               | A time container inside a template: name, time range, flexibility mode, default intents, constraints, context.            |
| **Flexibility mode**    | `strict` (fixed), `soft` (may shift within tolerance), `adaptive` (reflows freely).                                       |
| **Intent**              | A behavioral *mode/state* (e.g. recovery, deep work, movement). System library + user customs. Multi-intent with weights. |
| **Activity**            | An optional concrete expression of an intent (e.g. nap, bike ride, coding) with a duration estimate.                      |
| **DailyPlan**           | A specific date instantiated from a template; holds per-block decisions.                                                  |
| **Schedule**            | The compiled output: ordered `schedule_events` (block/buffer/transition). Versioned.                                      |
| **Check-in**            | A lightweight log on a plan block: started / completed / skipped + actual start/end.                                      |
| **Compiler**            | Deterministic engine turning a DailyPlan into a Schedule. **PHP only** (single source of truth); client shows a cosmetic optimistic preview, server confirms. |

## The four layers

1. **Template layer** — static recurring structure (docs 04).
2. **Instantiation layer** — daily decisions per block (docs 06).
3. **Compiler layer** — decisions → timeline (doc 07).
4. **Runtime layer** — live edits + downstream reflow (doc 08).

Around the core: intents/activities (05), check-ins/history (09), local intelligence (10),
visualization (12), plugins (14), and Google Calendar sync (15).
*(Doc 11 was intentionally removed — no AI assistant. The `11` number is retired; later docs keep
their numbers to avoid churning cross-references.)*
**Cross-cutting:** the design system, mobile-first layouts, touch model, and PWA/offline live in
`18-design-system.md` and apply to every frontend doc.

## Tech stack

- **Backend:** Laravel 12 (PHP 8.3+), Eloquent, form-request validation, policies, API resources.
- **Frontend:** Vue 3 (`<script setup lang="ts">`), Inertia.js, TypeScript, Tailwind CSS,
  shadcn-vue / Reka UI components, Pinia for cross-page client state only.
- **Mobile/PWA:** mobile-first UI, **installable** PWA (`vite-plugin-pwa` — manifest + service
  worker), touch-native gestures, design system + tokens. Offline *data*/sync is deferred. See `18`.
- **Time:** per-user `timezone` + configurable `day_start` (logical day boundary); all scheduling
  math is minutes-from-day_start (doc 07).
- **DB:** SQLite for dev (MySQL/Postgres compatible — use portable migrations).
- **Auth:** Laravel Breeze (Vue + Inertia + TS preset). Multi-user; all data scoped per user.
- **No AI/LLM at runtime.** The app is fully self-contained; all "smart" behavior is the local,
  deterministic intelligence layer (doc 10) over the user's own data. No external AI services.
- **Testing:** Pest (PHP) for features/units incl. compiler fixtures; Vitest for Vue components.
- **Tooling:** Vite, ESLint + Prettier, Pint for PHP.

## Repo conventions

- Ownership enforced by **policies** on every user-owned model; controllers authorize.
- Validation in **Form Request** classes, never inline.
- Inertia props serialized via **Eloquent API Resources** (typed on the TS side).
- Times stored as `TIME`/`DATETIME`; the compiler works in **minutes-from-`day_start`** integers
  internally for deterministic math (doc 07).
- Enums are PHP backed enums + matching TS union types in `resources/js/types`.
- One feature = one doc = its own migrations, model(s), controller(s), requests, resources,
  policy, Vue pages/components, and Pest tests.
- **Validation defaults** (every Form Request): reject unknown enum values; `end_time > start_time`;
  durations/minutes are positive ints; `days_of_week` ⊂ 0–6; sane string lengths; ownership checked
  by policy not just validation. Treat empty/cold-start states as valid (see per-feature docs), never
  as errors.

## How to read this doc set

- `01` architecture → `02` data model → `03` foundation are prerequisites for everything.
- Then build features in numeric order; each lists explicit **Depends on**.
- `16-build-order.md` is the master checklist with testing gates; `17` defines test strategy.

## Acceptance criteria
- Reader can name the four layers and the difference between Block, Intent, Activity.
- Reader knows the stack and that compiler logic is PHP-only (no client compiler in this build).

## Depends on
- Nothing. Entry point.
