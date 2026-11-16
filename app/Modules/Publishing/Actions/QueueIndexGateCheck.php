<?php

namespace App\Modules\Publishing\Actions;

use App\Modules\Publishing\IndexGate;
use App\Modules\Publishing\Jobs\RecomputeIndexableJob;

/**
 * Queues a recompute of one occupation's `indexable` flag, after the current transaction commits
 * (PLAN M14). Nothing is queued while the index gate is off: then no page is indexable anyway,
 * because the page and the sitemap check the gate too. Returns whether a job was queued.
 */
final class QueueIndexGateCheck
{
    public function __construct(private readonly IndexGate $gate) {}

    public function handle(int $occupationId): bool
    {
        if (! $this->gate->enabled()) {
            return false;
        }

        RecomputeIndexableJob::dispatch($occupationId)->afterCommit();

        return true;
    }
}
