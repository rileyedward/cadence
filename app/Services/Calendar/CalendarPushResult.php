<?php

namespace App\Services\Calendar;

final class CalendarPushResult
{
    public function __construct(
        public readonly int $created = 0,
        public readonly int $updated = 0,
        public readonly int $deleted = 0,
        public readonly bool $skipped = false,
    ) {}

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'created' => $this->created,
            'updated' => $this->updated,
            'deleted' => $this->deleted,
            'skipped' => $this->skipped,
        ];
    }
}
