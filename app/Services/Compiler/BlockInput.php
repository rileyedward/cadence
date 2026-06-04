<?php

namespace App\Services\Compiler;

/**
 * Eloquent-free input to the compiler. All times are minutes-from-day_start
 * integers (doc 07); endMin is always > startMin (the builder wraps +1440).
 */
final class BlockInput
{
    /**
     * @param  array{minDuration?:int,maxDuration?:int,minRestAfter?:int,noOverlap?:bool}  $constraints
     * @param  array<int,int>  $activityMinutes
     * @param  array<string,mixed>  $meta
     */
    public function __construct(
        public readonly int $id,
        public readonly string $label,
        public readonly int $startMin,
        public readonly int $endMin,
        public readonly string $mode,            // strict | soft | adaptive
        public readonly ?string $energy = null,  // low | medium | high
        public readonly ?string $focus = null,   // light | normal | deep
        public readonly int $priority = 0,
        public readonly int $order = 0,
        public readonly array $constraints = [],
        public readonly array $activityMinutes = [],
        public readonly ?string $mobility = null,
        public readonly array $meta = [],
    ) {}

    public function windowLength(): int
    {
        return $this->endMin - $this->startMin;
    }

    public function isStrict(): bool
    {
        return $this->mode === 'strict';
    }

    public function estimatedMinutes(): int
    {
        $sum = array_sum($this->activityMinutes);

        return $sum > 0 ? $sum : $this->windowLength();
    }

    public function isHighDeep(): bool
    {
        return $this->energy === 'high' && $this->focus === 'deep';
    }
}
