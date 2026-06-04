<?php

namespace App\Plugins\Samples;

use App\Plugins\Contracts\BlockType;

/**
 * Sample block type: a strict focus sprint with a 25–50 min window.
 */
class FocusSprintBlockType implements BlockType
{
    public function key(): string
    {
        return 'focus-sprint';
    }

    public function name(): string
    {
        return 'Focus Sprint';
    }

    public function defaults(): array
    {
        return [
            'flexibility_mode' => 'strict',
            'constraints' => ['minDuration' => 25, 'maxDuration' => 50],
            'context' => ['mobility' => 'stationary'],
        ];
    }
}
