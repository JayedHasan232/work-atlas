<?php

namespace App\Modules\AiPipeline\Enums;

/**
 * The state of one AI model call. Failed runs keep their error.
 */
enum AiRunStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
}
