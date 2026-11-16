<?php

namespace App\Modules\Knowledge\Enums;

/**
 * A reviewer's decision on a revision (D1).
 */
enum ReviewDecision: string
{
    case Accepted = 'accepted';
    case Rejected = 'rejected';
}
