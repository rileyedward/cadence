<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum SocialContext: string
{
    use HasValues;

    case Solo = 'solo';
    case Social = 'social';
    case Mixed = 'mixed';
}
