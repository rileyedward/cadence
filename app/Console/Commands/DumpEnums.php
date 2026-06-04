<?php

namespace App\Console\Commands;

use App\Enums\CheckInStatus;
use App\Enums\EnergyLevel;
use App\Enums\EventType;
use App\Enums\FlexibilityMode;
use App\Enums\FocusIntensity;
use App\Enums\MobilityPref;
use App\Enums\PlanStatus;
use App\Enums\SocialContext;
use App\Enums\TemplateScope;
use Illuminate\Console\Command;

class DumpEnums extends Command
{
    protected $signature = 'cadence:dump-enums';

    protected $description = 'Dump backed-enum values as JSON for the TS enum-parity test.';

    /** Enum short-name => FQCN. Keep in sync with resources/js/types/enums.ts. */
    private const ENUMS = [
        'FlexibilityMode' => FlexibilityMode::class,
        'EnergyLevel' => EnergyLevel::class,
        'FocusIntensity' => FocusIntensity::class,
        'SocialContext' => SocialContext::class,
        'MobilityPref' => MobilityPref::class,
        'TemplateScope' => TemplateScope::class,
        'PlanStatus' => PlanStatus::class,
        'EventType' => EventType::class,
        'CheckInStatus' => CheckInStatus::class,
    ];

    public function handle(): int
    {
        $out = [];
        foreach (self::ENUMS as $name => $class) {
            $out[$name] = $class::values();
        }

        $this->line(json_encode($out, JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }
}
