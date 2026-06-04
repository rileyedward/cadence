<?php

namespace App\Services\Compiler;

final class CompileConfig
{
    public function __construct(
        public readonly int $bufferMinutes = 5,
        public readonly int $minRestMinutes = 15,
        public readonly int $softToleranceMinutes = 30,
        public readonly bool $restAfterHighDeep = true,
        public readonly int $dayLengthMinutes = 1440,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            bufferMinutes: (int) config('cadence.buffer_minutes', 5),
            minRestMinutes: (int) config('cadence.min_rest_minutes', 15),
            softToleranceMinutes: (int) config('cadence.soft_tolerance_minutes', 30),
            restAfterHighDeep: (bool) config('cadence.energy_rules.rest_after_high_deep', true),
        );
    }
}
