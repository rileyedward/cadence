<?php

namespace App\Services\Compiler;

final class CompileResult
{
    /**
     * @param  array<int, EventDraft>  $events
     * @param  array<int, string>  $warnings
     */
    public function __construct(
        public readonly array $events,
        public readonly array $warnings = [],
    ) {}

    /**
     * @return array<int, array<string,mixed>>
     */
    public function toArray(): array
    {
        return array_map(fn (EventDraft $e) => $e->toArray(), $this->events);
    }
}
