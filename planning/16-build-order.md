# 16 — Build Order (Master Checklist)

> The agent's execution spine. Build top to bottom. Do not start a phase until the previous
> phase's testing gate passes. Each item links to its doc.

## Dependency graph (summary)
```
00 overview ─ 01 architecture ─ 02 data-model ─ 03 foundation
                                                   │
        ┌──────────────────────────┬──────────────┴───────────┐
   05 intents/activities      04 templates/blocks         13 frontend-arch
        └──────────┬───────────────┘                          │ (shared types/mirror,
                   ▼                                            │  build incrementally)
            06 daily-plans ──────► 07 compiler (PHP only)
                   │                       │
                   ▼                       ▼
            09 check-ins ◄──── 08 runtime-adjustments  ◄── MVP GATE after Phase 4
                   │
                   ▼
            10 intelligence (local, deterministic — no AI)
                   │
                   ▼
            12 visualization
                   │
                   ▼
            6b PWA installable shell (offline data deferred)
                   │
                   ▼
            14 plugins ──► 15 calendar (LAST)
                                   ▲
                            17 testing strategy (applies throughout)

   18 design-system + mobile-first + PWA shell  ── CROSS-CUTTING ──
   applies to every frontend phase (03/04/06/08/12); install scaffold in Phase 0,
   offline/sync as Phase 6b.
```

## Phases

> **Cross-cutting (doc 18):** every frontend phase below is **mobile-first**. Build the design
> tokens + mobile app shell (bottom nav → desktop sidebar) + PWA install scaffold in Phase 0, then
> follow doc 18's layouts/touch model in 04/06/08/12. Offline + sync is its own Phase 8b.

### Phase 0 — Foundations
- [ ] 01 architecture understood; directory layout created.
- [ ] 02 migrations + enums (PHP + TS) — `migrate:fresh` green.
- [ ] 03 Breeze auth, models, policies, factories, seeders (intent/activity library, sample template).
- [ ] 13 (partial) scaffold `types/`, `compiler/` dirs, base layout, Pinia.
- [ ] 18 (partial) design tokens + Tailwind theme, mobile app shell (bottom nav/sidebar), dark mode,
  PWA install scaffold (`vite-plugin-pwa` manifest + service worker, install to home screen).
- **Gate:** register/login works; `migrate:fresh --seed` works; policy 403 test passes;
  app is installable (Lighthouse PWA) and shell is usable at ~375px.

### Phase 1 — Structure
- [ ] 05 intents & activities (build before 04's intent picker).
- [ ] 04 templates, blocks, assignments, fork.
- **Gate:** create/fork template with blocks + default intents; `resolveForDate` test passes.

### Phase 2 — Planning
- [ ] 06 daily-plan instantiation + decision persistence (preview pane stubbed until 07).
- **Gate:** instantiate a date (snapshot test), save decisions, re-instantiate returns same plan.

### Phase 3 — The Engine (PHP only)
- [ ] 07 PHP `TimelineCompiler` + value objects + `generate` (+ optional `preview`) + `ScheduleWriter`
  with version pruning. Time model: per-user `timezone` + `day_start` (minutes-from-day_start).
- [ ] wire 06's live preview to a debounced server `preview` call (no client compiler).
- **Gate:** sample-day compile correct (buffers/rest/past-`day_start`); all edge-case fixtures pass
  (doc 07); version pruning works.

### Phase 4 — Runtime & Check-ins  ← **HARD MVP GATE**
- [ ] 09 check-ins + history queries + comparison DTO.
- [ ] 08 recompiler (freeze past/started/completed) + runtime ops + cosmetic optimistic transform.
- [ ] minimal Today timeline view to exercise the loop (full viz is Phase 6).
- **Gate:** frozen-event test; each op reflows correctly (server-computed); `fromMin` skew validated.
- 🛑 **Stop here and validate the end-to-end loop with a human** (template → plan → generate →
  check-in → adjust) before building Phases 5+. This is the MVP boundary.

### Phase 5 — Intelligence (local)
- [ ] 10 intelligence services (energy/habit/pattern/optimizer) on seeded history — local,
  deterministic, no AI/network. Powers "quick-fill from habits" + suggestion panels.
- **Gate:** intelligence fixtures pass; suggestions/recommendations are deterministic for a given
  seeded history; no external calls.

### Phase 6 — Visualization
- [ ] 12 timeline view, block-day view, weekly overview, history, actual-vs-planned (all mobile-first).
- **Gate:** sample day renders; view toggle; history aggregates render; overlay matches comparison;
  usable one-handed at ~375px.

### Phase 6b — PWA installable shell
- [ ] 18 manifest + service worker (`vite-plugin-pwa`): installable, precache shell/assets,
  network-first for data; placeholder icons; clear offline state (no silent failures).
- **Gate:** Lighthouse PWA pass; installs + launches standalone to Today; offline shows offline UI.

> **Deferred (post-first-build):** offline *data* — cache today + outbox queue (`stores/sync.ts`)
> with replay-on-reconnect. Captured in doc 18; not built in this pass.

### Phase 7 — Extensions
- [ ] 14 plugin contracts + registry + `cadence:sync-plugins` + sample module/block-type.
- **Gate:** sample module appears (idempotent); sample block type applies defaults.

### Phase 8 — Calendar (LAST)
- [ ] 15 Google Calendar OAuth, push (idempotent, tagged), import (strict-like anchors), settings UI.
- **Gate:** connect/disconnect; idempotent push; import respected by compiler; works with no config.

## Definition of done
- All gates green; `php artisan test` and `vitest` pass; Pint + ESLint clean.
- Sample template → instantiate today → generate → check-in → runtime-adjust → view history works
  end-to-end (manual run, doc 17 verification).
- Installable PWA (Lighthouse pass), mobile-first throughout (~375px one-handed), and the offline
  → reconnect-sync path works (doc 18).

## Depends on
- All feature docs; this is the index.
