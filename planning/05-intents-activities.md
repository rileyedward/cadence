# 05 — Intents & Activities

## Goal
The intent library (system + custom, multi-intent + weighting) and the activity library
(tagging, duration estimates, per-intent suggestions). These feed block defaults (04) and daily
plan decisions (06).

## Intents

### Library model
- System intents: `user_id null`, seeded in doc 03, read-only.
- Custom intents: owned by user. When a user "edits" a system intent, **clone** it into a custom
  one rather than mutating the shared row.

### Controller / routes
`IntentController`: `index` (system + own), `store`, `update`, `destroy` (custom only).
Inertia `Intents/Index` (manage library). Authorize custom-only writes via policy.

### Multi-intent + weighting
Intents are combined in two places:
- **Block defaults** (`block_default_intents`, weighted) — suggested options for a block.
- **Daily plan block** picks a primary `intent_id` + optional `secondary_intent_id`.
Weighting informs the local intelligence suggestions (doc 10) and ordering of pickers; it is data,
not a hard scheduling input.

## Activities

### Library model
- System + custom activities, same clone-on-edit rule as intents.
- `default_duration_minutes` is the planning estimate the compiler sums.
- `tags` json (string array) for filtering/search.

### Controller / routes
`ActivityController`: `index`, `store`, `update`, `destroy` (custom only).
Inertia `Activities/Index`.

### Per-intent suggestions
`intent_activity` pivot maps suggested activities to an intent (optionally weighted). Used to
populate the activity picker once an intent is chosen on a daily plan block, and as input to the
local intelligence suggestion layer (doc 10).

## Frontend
- `Intents/Index.vue` — grouped system vs custom, color/icon picker, create/edit/delete customs.
- `Activities/Index.vue` — table with duration, tags, linked intents; CRUD; tag filter.
- `Components/IntentBadge.vue`, `Components/ActivityPicker.vue` (filters by chosen intent's
  suggestions, falls back to full library + search).
- `Components/DurationInput.vue` (minutes, friendly h/m display).

## Acceptance criteria
- System library is visible and read-only; editing a system intent/activity creates a user copy.
- CRUD on custom intents/activities works and is user-scoped.
- Choosing an intent surfaces its suggested activities first in the picker.
- `intent_activity` and `block_default_intents` sync correctly with weights.

## Depends on
- `03-backend-foundation.md`
