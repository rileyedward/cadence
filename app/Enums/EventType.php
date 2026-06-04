<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum EventType: string
{
    use HasValues;

    case Block = 'block';
    case Buffer = 'buffer';
    case Transition = 'transition';
}
