<?php

use App\Enums\CheckInStatus;
use App\Enums\EnergyLevel;
use App\Enums\EventType;
use App\Enums\FlexibilityMode;
use App\Enums\FocusIntensity;
use App\Enums\MobilityPref;
use App\Enums\PlanStatus;
use App\Enums\SocialContext;
use App\Enums\TemplateScope;

/**
 * Canonical enum values. The Vitest enum-parity test compares the TS mirror
 * (resources/js/types/enums.ts) against `php artisan cadence:dump-enums`, which
 * is driven by these same PHP enums.
 */
dataset('enums', [
    [FlexibilityMode::class, ['strict', 'soft', 'adaptive']],
    [EnergyLevel::class, ['low', 'medium', 'high']],
    [FocusIntensity::class, ['light', 'normal', 'deep']],
    [SocialContext::class, ['solo', 'social', 'mixed']],
    [MobilityPref::class, ['stationary', 'mobile', 'mixed']],
    [TemplateScope::class, ['weekday', 'weekend', 'custom']],
    [PlanStatus::class, ['draft', 'generated', 'active', 'done']],
    [EventType::class, ['block', 'buffer', 'transition']],
    [CheckInStatus::class, ['started', 'completed', 'skipped']],
]);

it('exposes the expected backing values', function (string $enum, array $expected) {
    expect($enum::values())->toBe($expected);
})->with('enums');
