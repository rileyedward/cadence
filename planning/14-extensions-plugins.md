# 14 — Extensions & Plugins

## Goal
A plugin system for intent modules and custom block types, so capabilities can be added without
touching core. Keep it server-side and contract-driven.

## Concepts
- **Intent module** — packages a set of intents + suggested activities + optional suggestion
  logic (e.g. a "Fitness" module adding intents/activities and energy hints).
- **Custom block type** — a block variant with specialized defaults/behavior (e.g. a "Focus
  Sprint" type with preset flexibility + constraints).

## Contracts (`app/Plugins/`)
```php
interface IntentModule {
    public function key(): string;            // unique id
    public function name(): string;
    public function intents(): array;          // intent definitions to register
    public function activities(): array;       // activity definitions + intent links
    public function suggestions(?DailyPlanBlock $block): array; // optional, may return []
}

interface BlockType {
    public function key(): string;
    public function name(): string;
    public function defaults(): array;         // flexibility, constraints, context defaults
}
```

## Registry
- `PluginRegistry` (singleton) discovers modules registered in `config/cadence.php`
  (`plugins.intent_modules`, `plugins.block_types`) — explicit allow-list, no auto-scan of
  arbitrary code.
- On boot, registered modules' intents/activities are made available (as system-like library
  entries tagged with the module key) without duplicating rows on every boot (idempotent sync via
  a console command `cadence:sync-plugins`).
- Block types appear as options in the block editor (doc 04); selecting one applies its defaults.

## Surfacing
- Suggestion hooks feed the local intelligence layer (doc 10) as additional inputs, behind the
  same DTOs.
- Plugins never bypass policies or validation; their data flows through the normal models.

## Boundaries (keep it safe)
- No remote code loading. Modules are first-party PHP classes enabled via config.
- A future token API (out of scope here) could expose plugin endpoints; not built now.

## Acceptance criteria
- A sample `IntentModule` registered via config appears in the intent library after
  `cadence:sync-plugins` (idempotent — re-running doesn't duplicate).
- A sample `BlockType` appears in the block editor and applies its defaults on selection.
- Disabling a module in config removes its options without breaking existing data.

## Depends on
- `04-templates-blocks.md`, `05-intents-activities.md`, `10-intelligence-layer.md`.
