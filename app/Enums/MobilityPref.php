<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum MobilityPref: string
{
    use HasValues;

    case Stationary = 'stationary';
    case Mobile = 'mobile';
    case Mixed = 'mixed';
}
