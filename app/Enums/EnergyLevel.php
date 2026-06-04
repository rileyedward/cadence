<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum EnergyLevel: string
{
    use HasValues;

    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
}
