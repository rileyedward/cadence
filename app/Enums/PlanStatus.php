<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum PlanStatus: string
{
    use HasValues;

    case Draft = 'draft';
    case Generated = 'generated';
    case Active = 'active';
    case Done = 'done';
}
