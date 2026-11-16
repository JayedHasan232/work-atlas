<?php

namespace App\Modules\Knowledge\Queries;

use App\Modules\Knowledge\Models\Revision;

/**
 * Whether an AI run's output has already been written as revisions, so a rerun can skip it.
 */
final class RunHasRevisions
{
    public function handle(int $aiRunId): bool
    {
        return Revision::query()->where('ai_run_id', $aiRunId)->exists();
    }
}
