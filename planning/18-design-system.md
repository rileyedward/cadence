# 18 — Design System, Mobile-First UX & PWA

## Goal
Define the design language, the mobile-first layout system, touch interaction model, and the PWA
(installable + offline) behavior. This is a **cross-cutting** doc: every feature's frontend
(docs 04, 06, 08, 12) must follow it, and `13-frontend-architecture.md` references it for
conventions.

## Core stance
Cadence is used in two modes with different devices:
- **Planning** (template building, daily decisions) — often desktop, but **must work fully on
  mobile**.
- **Living the day** (timeline, check-ins, on-the-fly adjustments) — primarily **mobile, on the
  go, possibly offline**.

So: **mobile-first for all flows**, scaling up to desktop. Design the small screen first; desktop
is the enhancement, not the baseline.

## Design language

### Tokens (`resources/js/design/tokens.ts` + Tailwind theme extension)
- **Color:** neutral app surface; **intent color is the primary visual signal** — each intent
  carries a color (from its library row) used for block fills, badges, and timeline segments.
  Define semantic tokens: `surface`, `surface-elevated`, `border`, `text`, `text-muted`,
  `accent`, plus `intent.*` resolved at runtime from the intent's `color`.
- **Type scale:** mobile-readable base (16px min for inputs to avoid iOS zoom), modular scale.
- **Spacing/radius:** generous touch spacing; rounded cards. 4px base unit.
- **Elevation/motion:** subtle; motion respects `prefers-reduced-motion`.
- **Dark mode:** token-driven, both themes from day one.

### Components
- Built on **shadcn-vue / Reka UI** primitives in `Components/ui/`, themed via the tokens above.
- Cadence-specific components compose these: `BlockCard`, `IntentBadge`, `TimelineBoard`,
  `CheckInControls`, `BottomSheet`, `Stepper`.
- **Touch target minimum 44×44px** for all interactive elements.

## App shell & navigation (mobile-first)
- **Mobile:** bottom tab bar (`Components/AppShell/BottomNav.vue`) — primary destinations:
  **Today** (active day), **Plan** (calendar/instantiate), **Templates**, **Insights/History**.
  A top app bar holds context title + contextual actions.
- **Desktop (≥`lg`):** the bottom nav promotes to a **left sidebar**; content area widens and
  multi-pane layouts (e.g. planning two-pane) appear. Same components, responsive container.
- Use Tailwind breakpoints; design at `sm` first, add `md`/`lg` enhancements.

## Responsive layout rules per flow
- **Planning session (doc 06):** mobile = a **stepper / stacked flow** — one block decision card at
  a time (swipe or Next/Back), with a collapsible timeline preview drawer. Desktop = the two-pane
  (cards left, live preview right). Same data + debounced server-preview underneath (doc 07).
- **Live timeline (docs 08/12):** mobile = single vertical scrollable timeline with a sticky
  now-line and a floating "recompile rest of day" action; tapping a block opens a **bottom sheet**.
  Desktop = wider timeline with inline controls.
- **Template/block editor (doc 04):** mobile = stacked list of block cards with bottom-sheet
  editors; desktop = side-by-side editor.
- **History/Insights (doc 12):** mobile = stacked cards/charts, horizontally scrollable where
  needed; desktop = grid.

## Touch interaction model (live timeline)
Pointer Events power **both** mouse and touch via one composable `useDragResize`
(`resources/js/lib/useDragResize.ts`):
- **Move a future block:** long-press (≈250ms) to "grab" → drag → drop. Visual lift + snap to a
  time grid (e.g. 5-min increments). Light haptic on grab/drop (`navigator.vibrate` where available).
- **Resize:** drag the top/bottom **handle** to extend/shorten. Handles are enlarged on touch.
- **Frozen past** (already-happened / started / completed blocks) is non-draggable and visually
  locked.
- Every gesture applies a **cosmetic optimistic transform** (move the touched event + shift the
  next ones by the delta) for instant feel, then posts the op to the server (doc 08). The server
  recompiles and its schedule replaces the optimistic state — server wins.
- Gestures must not fight page scroll: grab requires the long-press; otherwise vertical drag scrolls.
- Accessibility fallback: each gesture has an equivalent in the block's bottom sheet
  (extend/shorten/move-to-time/skip buttons) so the timeline is operable without dragging.

## PWA — installable shell (first build)

> **Scope for the first build = installable, not offline-data.** The app installs to the home
> screen and caches its shell/assets, but still needs the network to load and mutate data. Full
> offline (read today + outbox sync) is **deferred** — see "Deferred: offline data" below.

### Installable
- **Web app manifest** (`public/manifest.webmanifest`): name, short_name, icons (maskable),
  theme/background color, `display: standalone`, start_url `/today`.
- **Service worker** (build via `vite-plugin-pwa`): precache the app shell + static assets;
  runtime-cache fonts/icons. Network-first for data/document requests (no stale data served).
- iOS niceties: apple-touch-icon, status bar meta, safe-area insets (`env(safe-area-inset-*)`)
  honored in the shell.
- **Offline UX for now:** when offline, show a clear "you're offline" state; data reads/writes are
  unavailable until reconnect (no silent failures).

### Deferred: offline data (later phase, not first build)
Captured here so the architecture leaves room, but **do not build yet**:
- Cache today's `DailyPlan` + current `Schedule` + library for offline *read*.
- An **outbox** (`stores/sync.ts` + IndexedDB) queuing check-ins/runtime ops while offline, replayed
  on reconnect; the server `Recompiler` produces the authoritative schedule and **server wins**;
  replays hitting already-frozen blocks are no-ops. Because the compiler is server-only (doc 07),
  offline edits would be cosmetic until sync. Revisit once core is stable.

## Config & assets
- App icons + splash in `public/icons/`. Manifest + SW registration wired in `app.ts`.
- PWA build options (precache globs, runtime caching) in `vite.config.ts` via `vite-plugin-pwa`.
- **Icon assets:** an agent can't design these — generate simple placeholder icons (a solid
  Cadence glyph at the required sizes) so installability passes; real art is a later task.

## Acceptance criteria
- App is installable (Lighthouse PWA pass) on Android/iOS; launches standalone to **Today**.
- Every flow is usable one-handed on a ~375px-wide viewport; touch targets ≥44px; no iOS input
  zoom; safe-area insets respected.
- Planning session renders as a stepper on mobile and two-pane on desktop from the same components.
- Live timeline supports long-press move + handle resize on touch, with a button fallback in the
  bottom sheet; frozen past is non-interactive.
- Installed app launches standalone to **Today**; when offline it shows a clear offline state
  (no silent failures). Offline *data*/sync is explicitly out of the first build.
- Dark mode + `prefers-reduced-motion` honored.

## Depends on
- `13-frontend-architecture.md` (conventions, Pinia), and applies across `04`, `06`, `08`, `12`.
