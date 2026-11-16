<?php

namespace App\Modules\Taxonomy\Enums;

/**
 * What one unit-group expansion did (PLAN M10).
 */
enum ExpansionStatus: string
{
    // The model's list was written as candidate occupations.
    case Expanded = 'expanded';

    // An earlier run with the same input already wrote its list (or proposed nothing); no call was made.
    case Skipped = 'skipped';

    // The model call failed: provider error, invalid JSON or output that breaks the schema.
    case Failed = 'failed';

    // The output passed the schema but broke a Knowledge rule (for example a name listed twice), so
    // nothing was written.
    case Rejected = 'rejected';
}
