<?php

namespace App\Enums\Concerns;

trait HasValues
{
    /**
     * All backing values for this enum.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
