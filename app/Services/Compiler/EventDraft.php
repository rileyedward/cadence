<?php

namespace App\Services\Compiler;

/**
 * One emitted timeline event (block | buffer | transition). Times are
 * minutes-from-day_start; the writer maps them to clock times + stashes the
 * integer minutes in metadata for unambiguous rendering (doc 02 note).
 */
final class EventDraft
{
    /**
     * @param  array<string,mixed>  $metadata
     */
    public function __construct(
        public readonly string $type,          // block | buffer | transition
        public readonly string $label,
        public readonly int $startMin,
        public readonly int $endMin,
        public readonly ?int $sourceBlockId = null,
        public array $metadata = [],
    ) {}

    public function durationMin(): int
    {
        return $this->endMin - $this->startMin;
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'label' => $this->label,
            'startMin' => $this->startMin,
            'endMin' => $this->endMin,
            'sourceBlockId' => $this->sourceBlockId,
            'metadata' => $this->metadata,
        ];
    }
}
