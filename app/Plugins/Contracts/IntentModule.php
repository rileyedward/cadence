<?php

namespace App\Plugins\Contracts;

use App\Models\DailyPlanBlock;

/**
 * Packages a set of intents + suggested activities (+ optional suggestion logic)
 * that can be enabled via config without touching core (doc 14).
 */
interface IntentModule
{
    /** Unique, stable module key (also stored on rows as provenance). */
    public function key(): string;

    public function name(): string;

    /**
     * Intent definitions to register.
     *
     * @return array<int, array{name:string, slug:string, color?:string, icon?:string, description?:string}>
     */
    public function intents(): array;

    /**
     * Activity definitions + the intent slugs they're suggested for.
     *
     * @return array<int, array{name:string, default_duration_minutes:int, tags?:array<int,string>, intents?:array<int,string>}>
     */
    public function activities(): array;

    /**
     * Optional contextual suggestions feeding the intelligence layer (may return []).
     *
     * @return array<int, array<string,mixed>>
     */
    public function suggestions(?DailyPlanBlock $block): array;
}
