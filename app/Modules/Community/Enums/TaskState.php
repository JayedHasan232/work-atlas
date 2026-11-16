<?php

namespace App\Modules\Community\Enums;

/**
 * Where a micro-task stands (PLAN M17). Supported and disputed are the consensus outcomes; stale
 * means the value changed before there was one.
 */
enum TaskState: string
{
    case Open = 'open';
    case Supported = 'supported';
    case Disputed = 'disputed';
    case Stale = 'stale';
}
