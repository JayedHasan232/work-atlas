<?php

namespace App\Modules\Publishing\Jobs;

use App\Modules\Publishing\Actions\EvaluateIndexGate;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Recomputes one occupation's `indexable` after a value or status change (PLAN M14). Unique per
 * occupation while queued, so a run that writes many fields queues it once.
 */
final class RecomputeIndexableJob implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Queueable;

    /**
     * The lock ends by itself, so a job lost before it ran (queue flushed, worker killed) does not
     * block this occupation's recompute for ever.
     */
    public int $uniqueFor = 600;

    public function __construct(public readonly int $occupationId) {}

    public function uniqueId(): string
    {
        return (string) $this->occupationId;
    }

    public function handle(EvaluateIndexGate $evaluate): void
    {
        $evaluate->handle($this->occupationId);
    }
}
