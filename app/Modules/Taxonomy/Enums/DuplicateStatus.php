<?php

namespace App\Modules\Taxonomy\Enums;

/**
 * A possible duplicate pair (M11). Once decided, a pair is never flagged again (D47).
 */
enum DuplicateStatus: string
{
    case Open = 'open';
    case Merged = 'merged';
    case KeptSeparate = 'kept_separate';
}
