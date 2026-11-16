<?php

namespace App\Modules\AiPipeline\Enums;

/**
 * What one occupation's Wave 1 generation did (PLAN M12).
 */
enum Wave1Status: string
{
    /** Both models answered and every value was written. */
    case Generated = 'generated';
    /** The same runs were written before; nothing new. */
    case Skipped = 'skipped';
    /** A model call failed or its output did not fit the schema; nothing was written. */
    case Failed = 'failed';
    /** A value broke a Knowledge rule; nothing was written. */
    case Rejected = 'rejected';
}
