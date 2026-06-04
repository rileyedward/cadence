<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum TemplateScope: string
{
    use HasValues;

    case Weekday = 'weekday';
    case Weekend = 'weekend';
    case Custom = 'custom';
}
