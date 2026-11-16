<?php

namespace App\Modules\Knowledge\Enums;

/**
 * Whether two models gave the same value for a slot (PLAN M12, D50).
 */
enum CrossCheck: string
{
    case Agreed = 'agreed';
    case Disagreed = 'disagreed';
}
