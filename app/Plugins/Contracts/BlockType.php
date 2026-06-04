<?php

namespace App\Plugins\Contracts;

/**
 * A block variant with specialized defaults (e.g. a "Focus Sprint" with preset
 * flexibility + constraints). Surfaces as an option in the block editor (doc 14).
 */
interface BlockType
{
    public function key(): string;

    public function name(): string;

    /**
     * Default attributes applied when this type is selected.
     *
     * @return array{flexibility_mode?:string, constraints?:array<string,mixed>, context?:array<string,mixed>}
     */
    public function defaults(): array;
}
