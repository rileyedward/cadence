# 04 — Templates & Blocks

## Goal
CRUD for templates and their blocks, weekly assignment, fork/duplicate, and all block
attributes (flexibility mode, constraints, context, category, priority, dependencies).

## Templates

### Controller / routes
`TemplateController`: `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`,
plus `fork` (POST `/templates/{template}/fork`).
- All scoped to `auth()->user()`; authorize via `TemplatePolicy`.
- `index` → Inertia `Templates/Index` (list with assignment summary).
- `show`/`edit` → `Templates/Edit` (template meta + block editor).

### Fork / duplicate
`fork` deep-copies template + blocks + `block_default_intents` + dependencies, sets
`forked_from_id`, appends "(copy)" to name. New owner = current user.

### Weekly assignment
`TemplateAssignmentController` (or nested actions): create/update/delete assignments.
- `scope` = weekday | weekend | custom; `custom` uses `days_of_week` (0–6).
- Optional `starts_on`/`ends_on` for seasonal/situational/travel templates.
- `priority` resolves overlaps. Provide a helper `Template::resolveForDate(User,$date)` that
  returns the highest-priority active assignment matching the date (used by doc 06).

## Blocks

### Controller / routes
`BlockController` nested under template: `store`, `update`, `destroy`, `reorder`
(POST list of ids→order). Authorize via parent template policy.

### Attributes (validated in form requests)
- `name`, `start_time`, `end_time` (end after start; warn—not block—on overlaps, since soft
  windows may intentionally overlap).
- `flexibility_mode` enum.
- `category`, `priority`.
- `constraints` json: `{ minDuration?, maxDuration?, minRestAfter?, noOverlap? }`.
- `context` json: `{ location?, mobility?, social? }` defaults inherited into daily plan.
- `default_intents`: array of `{ intent_id, weight }` → sync `block_default_intents`.

### Dependencies
`block_dependencies`: optional `after`/`requires` links. Surface in editor; the compiler (doc 07)
uses them only for ordering hints, not hard scheduling (blocks already have times).

## Frontend
- `Templates/Index.vue` — card list, create button, assignment badges, fork/delete actions.
- `Templates/Edit.vue` — template meta form + **block editor**: ordered list of block cards,
  add/edit/remove, drag to reorder, inline time pickers, flexibility-mode selector, default-intent
  multi-select with weight, constraints/context disclosure.
- `Components/BlockCard.vue`, `Components/IntentMultiSelect.vue`, `Components/TimeRangeInput.vue`.
- A mini day-strip preview showing blocks laid out by time (read-only; full timeline is doc 12).

## Acceptance criteria
- Create a template, add blocks with all attributes, reorder, edit, delete.
- Fork produces an independent deep copy (editing copy doesn't touch original).
- Assignments resolve: `Template::resolveForDate` returns correct template for a given weekday vs
  weekend vs custom vs date-range (Pest test).
- Cross-user access blocked.

## Depends on
- `03-backend-foundation.md`, `05-intents-activities.md` (for default-intent picker; build 05 first
  or stub the picker).
