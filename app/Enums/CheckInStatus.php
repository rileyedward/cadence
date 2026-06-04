<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum CheckInStatus: string
{
    use HasValues;

    case Started = 'started';
    case Completed = 'completed';
    case Skipped = 'skipped';
}
