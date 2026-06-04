<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum FocusIntensity: string
{
    use HasValues;

    case Light = 'light';
    case Normal = 'normal';
    case Deep = 'deep';
}
