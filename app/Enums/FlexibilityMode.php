<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum FlexibilityMode: string
{
    use HasValues;

    case Strict = 'strict';
    case Soft = 'soft';
    case Adaptive = 'adaptive';
}
