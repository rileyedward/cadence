# 03 — Backend Foundation

## Goal
Stand up the Laravel 12 + Inertia + Vue + TS skeleton, auth, all migrations/models from doc 02,
ownership policies, and seeders (system intent/activity library + a sample template). After this
doc the app boots, you can register/login, and the schema + base data exist.

## Setup steps

1. Create project with the `laravel new` command and select all options accordingly for this projects' tech stack.
2. Configure `.env` for SQLite: `DB_CONNECTION=sqlite`, create `database/database.sqlite`.
3. Install **Breeze** with the Vue + Inertia + TypeScript preset:
   `composer require laravel/breeze --dev` → `php artisan breeze:install vue --typescript`. This will be confirued in the `laravel new` step 1.
   → `npm install`. Confirm SSR off for now. Most of hte time this is  already installed with the laravel started project. This will be confirued in the `laravel new` step 1.
4. Add **Tailwind** (Breeze includes it) + **shadcn-vue / Reka UI** and a base component set. This will be confirued in the `laravel new` step 1.
5. Add **Pinia**: `npm i pinia`, register in `resources/js/app.ts`.
6. Add **Pest**: `composer require pestphp/pest pestphp/pest-plugin-laravel --dev` →
   `php artisan pest:install`. Add **Vitest** + `@vue/test-utils` for TS. This will be confirued in the `laravel new` step 1.
7. Add **Pint** + ESLint/Prettier configs. All of this should also be in the starter kit.

In a perfect world, all of this is mostly there with a simple `laravel new` command with options there, but giving them some more in-depth configuration.

## Models & migrations
Create every table + enum from `02-data-model.md`. Each model:
- `$fillable`/`$casts` (enums cast to backed enum, json cast to array/`AsArrayObject`).
- All relationships from doc 02's relationship summary.
- Factories for every model.

Enum classes in `app/Enums/`; TS mirror `resources/js/types/enums.ts`.

## Auth & ownership
- Breeze gives register/login/profile.
- Add a **Policy** per user-owned model: `Template`, `Intent` (custom only), `Activity`
  (custom only), `DailyPlan`, and cascade-checked children (`Block` via its template,
  `DailyPlanBlock`/`CheckIn` via plan).
- Register policies; controllers call `$this->authorize(...)`. System-library intents/activities
  (`user_id null`) are read-only to users (cloned on customize).

## HandleInertiaRequests shared props
Share: `auth.user`, flash messages, and a lightweight `intentLibrary` (system + user intents)
for pickers. Keep payloads small; lazy-load big lists per page.

## Seeders
- **IntentLibrarySeeder** — system intents from the spec's spirit: e.g. `recovery`, `deep-work`,
  `movement`, `food`, `bonding`, `creative`, `leisure`, `errands`, `wind-down`, `commute`,
  with colors/icons.
- **ActivityLibrarySeeder** — sample activities (nap, cat time, bike ride, dinner run, errands,
  coding, gaming, reading, morning routine) with `default_duration_minutes`, linked to intents
  via `intent_activity`.
- **SampleTemplateSeeder** (dev/demo only, gated) — the spec's example day:
  07:00 Morning Routine, 08:00 Commute Prep, 08:30 Work (strict), 16:00 Commute Home,
  16:30 Recovery (adaptive), 18:00 Activation (soft), 20:00 Freedom (soft/adaptive),
  01:30 Wind Down. Attach default intents + flexibility modes per the spec defaults.
- Default flexibility per spec: Recovery=adaptive, Activation=soft, Freedom=soft/adaptive,
  Work=strict.

## config/cadence.php (scaffold)
Create with default keys (filled by later docs): `buffer_minutes`, `min_rest_minutes`,
`energy_rules`, `intelligence` (thresholds), `calendar` (driver/credentials).

## Acceptance criteria
- `npm run dev` + `php artisan serve` boot; register/login works.
- `php artisan migrate:fresh --seed` creates schema + system intent/activity library.
- Policies block cross-user access (Pest test: user A cannot view user B's template → 403).
- Factories exist for all models; `php artisan test` green.

## Depends on
- `02-data-model.md`
