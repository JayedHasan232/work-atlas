<?php

namespace App\Modules\Community\Jobs;

use App\Modules\Community\Actions\EvaluateConsensus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Checks one micro-task for a consensus after an answer (PLAN M17).
 */
final class EvaluateConsensusJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $taskId) {}

    public function handle(EvaluateConsensus $evaluate): void
    {
        $evaluate->handle($this->taskId);
    }
}
