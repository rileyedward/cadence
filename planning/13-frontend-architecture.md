# 13 — Frontend Architecture

## Goal
Define the Vue 3 + Inertia + TS conventions, shared types, Pinia usage, and chosen libraries — so
every feature doc's frontend slots in consistently. (The compiler is PHP-only; no client compiler.)

> **Design + PWA live in `18-design-system.md`.** This doc covers *code structure*; doc 18 covers
> the design language, mobile-first layouts, touch model, and offline/PWA. Build them together —
> every page here is **mobile-first** per doc 18.

## Stack & conventions
- Vue 3 `<script setup lang="ts">` everywhere. No Options API.
- Inertia pages in `resources/js/Pages/**`; layouts in `resources/js/Layouts/`.
- Tailwind utility-first; shared UI via **shadcn-vue / Reka UI** primitives in `Components/ui/`,
  themed via the design tokens in doc 18 (`resources/js/design/tokens.ts`).
- **Mobile-first:** author at the `sm` viewport, enhance up with `md`/`lg`. App shell is a bottom
  nav on mobile that promotes to a sidebar on desktop (doc 18).
- Props typed via `defineProps<...>()`; page props mirror backend API resources.
- Forms via Inertia `useForm`; mutations via `router.{post,put,delete}` returning to refreshed props.

## Shared types (`resources/js/types/`)
- `enums.ts` — TS unions mirroring doc-02 PHP enums (single source for both languages' values).
- `models.ts` — interfaces mirroring API resources (`TemplateDTO`, `BlockDTO`, `IntentDTO`,
  `ActivityDTO`, `DailyPlanDTO`, `DailyPlanBlockDTO`, `ScheduleDTO`, `ScheduleEventDTO`,
  `CheckInDTO`, `PlanBlockComparison`).
- Keep these hand-synced with backend resources (a small test asserts enum value parity).

## No client compiler
The schedule is computed only in PHP (doc 07). The client renders the server's `ScheduleDTO` and,
during a drag, applies a **cosmetic optimistic transform** (move the touched event + shift the next
ones by the delta) before the server returns the authoritative result. There is no `resources/js/
compiler/` directory in this build; a TS port is deferred.

## State (Pinia, `resources/js/stores/`)
Use Pinia **only** for cross-page / ephemeral client state:
- `runtime.ts` — optimistic-transform state during drag/resize + in-flight/reconciled status (doc 08).
- `planningPreview.ts` — debounced server-preview state in the planning session (doc 06).
Everything server-owned flows through Inertia props, not Pinia.
*(Deferred: a `sync.ts` offline outbox — see doc 18; not in the first build.)*

## Libraries (decisions)
- **Drag/resize + touch:** native **Pointer Events** via one composable `useDragResize`
  (`resources/js/lib/useDragResize.ts`) covering mouse + touch (long-press grab, handle resize,
  haptics) — see doc 18 touch model. Avoid heavy DnD frameworks.
- **PWA:** `vite-plugin-pwa` for an **installable shell** (manifest + service worker, precache app
  shell + asset caching). Offline *data* is deferred (doc 18).
- **Charts (doc 12):** lightweight — hand-rolled SVG or `chart.js`. Pick one; keep bundle small.
- **Dates:** `date-fns` (tree-shakeable).
- **Icons:** the icon set Breeze/shadcn-vue ships (e.g. lucide).

## Lib utils (`resources/js/lib/`)
- `timeScale.ts` — shared time→pixel mapping for all timeline views (doc 12).
- `time.ts` — display formatting + minutes/`day_start` conversions for rendering (not scheduling).

## Acceptance criteria
- Enum values match between `types/enums.ts` and PHP enums (parity test).
- No client-side schedule computation; the client renders server `ScheduleDTO` + a cosmetic
  optimistic drag transform only.
- Pinia holds only client/ephemeral state; server data comes via Inertia props.
- A new feature page can be added following these conventions without inventing new patterns.

## Depends on
- `01-architecture.md`, `07-timeline-compiler.md` (server-only), `18-design-system.md`.
