# 02 — Data Model

## Goal
Define every table, column, relationship, and enum. This is the canonical schema; all later
docs reference these names. Migrations are portable (SQLite dev, MySQL/Postgres safe).

## Enums (PHP backed enums + TS unions)

```
FlexibilityMode: strict | soft | adaptive
EnergyLevel:     low | medium | high
FocusIntensity:  light | normal | deep
SocialContext:   solo | social | mixed
MobilityPref:    stationary | mobile | mixed
TemplateScope:   weekday | weekend | custom
PlanStatus:      draft | generated | active | done
EventType:       block | buffer | transition
CheckInStatus:   started | completed | skipped
```

Place PHP enums in `app/Enums/`, TS mirrors in `resources/js/types/enums.ts`.

## Tables

### users
Laravel Breeze default (`id`, `first_name`, `last_name`, `email`, `password`, timestamps) **plus**:
| col            | type                       | notes                                                        |
| -------------- | -------------------------- | ------------------------------------------------------------ |
| timezone       | string default 'UTC'       | IANA tz; all date/time math resolves through this (doc 07)    |
| day_start_time | time default '04:00'       | logical day boundary / wake-time; blocks before it belong to the previous logical day |

### templates
| col            | type                  | notes                         |
| -------------- | --------------------- | ----------------------------- |
| id             | pk                    |                               |
| user_id        | fk users              | cascade delete                |
| name           | string                |                               |
| description    | text nullable         |                               |
| is_active      | bool default true     |                               |
| forked_from_id | fk templates nullable | provenance for duplicate/fork |
| timestamps     |                       |                               |

### template_assignments
Maps a template to days. A user resolves "today's template" by matching assignments.
| col | type | notes |
|-----|------|-------|
| id | pk | |
| template_id | fk templates | cascade |
| scope | enum TemplateScope | |
| days_of_week | json nullable | array 0–6 for `custom` |
| starts_on / ends_on | date nullable | seasonal/situational range |
| priority | int default 0 | higher wins on conflict |
| timestamps | | |

### blocks
| col              | type                 | notes                                             |
| ---------------- | -------------------- | ------------------------------------------------- |
| id               | pk                   |                                                   |
| template_id      | fk templates         | cascade                                           |
| name             | string               |                                                   |
| start_time       | time                 |                                                   |
| end_time         | time                 |                                                   |
| flexibility_mode | enum FlexibilityMode |                                                   |
| category         | string nullable      | block category/grouping                           |
| priority         | int default 0        | reflow tie-breaking                               |
| constraints      | json nullable        | `{ minDuration, maxDuration, minRestAfter, ... }` |
| context          | json nullable        | `{ location, mobility, social }` defaults         |
| order            | int                  | sort within template                              |
| timestamps       |                      |                                                   |

### block_dependencies
Optional ordering/prerequisite links between blocks.
| col | type | notes |
|-----|------|-------|
| id | pk | |
| block_id | fk blocks | cascade |
| depends_on_block_id | fk blocks | cascade |
| type | string | e.g. `after`, `requires` |

### intents
| col         | type              | notes                 |
| ----------- | ----------------- | --------------------- |
| id          | pk                |                       |
| user_id     | fk users nullable | null = system library |
| name        | string            |                       |
| slug        | string            | unique per scope      |
| color       | string nullable   |                       |
| icon        | string nullable   |                       |
| description | text nullable     |                       |
| timestamps  |                   |                       |

### activities
| col                      | type              | notes                 |     |
| ------------------------ | ----------------- | --------------------- | --- |
| id                       | pk                |                       |     |
| user_id                  | fk users nullable | null = system library |     |
| name                     | string            |                       |     |
| default_duration_minutes | int               | duration estimate     |     |
| tags                     | json nullable     | string array          |     |
| timestamps               |                   |                       |     |

### intent_activity (pivot)
Suggested activities per intent. `intent_id`, `activity_id`, optional `weight` int.

### block_default_intents (pivot)
Default intent options for a block. `block_id`, `intent_id`, `weight` int default 1.

### daily_plans
| col | type | notes |
|-----|------|-------|
| id | pk | |
| user_id | fk users | cascade |
| template_id | fk templates nullable | nullSet on template delete |
| date | date | unique per (user_id, date) |
| status | enum PlanStatus default draft | |
| timestamps | | |

### daily_plan_blocks
Snapshot of decisions for one block on one day. Copies the block's window at instantiation so
template edits don't retroactively alter past plans.
| col | type | notes |
|-----|------|-------|
| id | pk | |
| daily_plan_id | fk daily_plans | cascade |
| block_id | fk blocks nullable | source block (nullSet if block deleted) |
| name | string | snapshot of block name |
| start_time | time | snapshot |
| end_time | time | snapshot |
| flexibility_mode | enum FlexibilityMode | snapshot |
| intent_id | fk intents nullable | |
| secondary_intent_id | fk intents nullable | |
| energy_level | enum EnergyLevel nullable | |
| focus_intensity | enum FocusIntensity nullable | |
| social_context | enum SocialContext nullable | |
| mobility_preference | enum MobilityPref nullable | |
| constraints | json nullable | per-day overrides |
| context_tags | json nullable | |
| order | int | |
| timestamps | | |

### daily_plan_block_activities (pivot)
`daily_plan_block_id`, `activity_id`, `order` int, `estimated_minutes` int (defaults from
activity but editable per day).

### schedules
| col | type | notes |
|-----|------|-------|
| id | pk | |
| daily_plan_id | fk daily_plans | cascade |
| version | int | increments per recompile |
| generated_at | datetime | |
| is_current | bool default true | one current per plan |
| timestamps | | |

### schedule_events
| col | type | notes |
|-----|------|-------|
| id | pk | |
| schedule_id | fk schedules | cascade |
| source_block_id | fk daily_plan_blocks nullable | null for buffer/transition |
| type | enum EventType | |
| label | string | |
| start_time | time | |
| end_time | time | |
| order | int | |
| metadata | json nullable | intents, energy, flags (e.g. `restInserted`) |

### check_ins
| col | type | notes |
|-----|------|-------|
| id | pk | |
| daily_plan_block_id | fk daily_plan_blocks | cascade |
| status | enum CheckInStatus | |
| actual_start | datetime nullable | |
| actual_end | datetime nullable | |
| note | text nullable | |
| timestamps | | |

## Relationship summary
- User hasMany Templates, Intents (custom), Activities (custom), DailyPlans.
- Template hasMany Blocks, TemplateAssignments; belongsTo forkedFrom (self).
- Block belongsToMany Intents (defaults, weighted); hasMany BlockDependencies.
- Intent belongsToMany Activities (suggestions).
- DailyPlan belongsTo Template; hasMany DailyPlanBlocks; hasMany Schedules (one `is_current`).
- DailyPlanBlock belongsTo Intent + secondaryIntent; belongsToMany Activities (with order +
  estimated_minutes); hasMany CheckIns.
- Schedule hasMany ScheduleEvents.

## Acceptance criteria
- `php artisan migrate` succeeds on SQLite with all tables + FKs above.
- Every enum exists as a PHP backed enum and a TS union in `types/enums.ts`.
- Past plans are unaffected by later template edits (snapshot columns verified by a test).

## Depends on
- `01-architecture.md`
