<?php

namespace App\Modules\Taxonomy\Jobs;

use App\Modules\Taxonomy\Actions\ExpandUnitGroup;
use App\Modules\Taxonomy\Enums\ExpansionStatus;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Expands one unit group on the queue (every AI call runs in a job). It runs once: the gateway retries
 * provider errors itself, and a second attempt would make a second run. One job per unit group at a
 * time: two at once would pay twice and write the same candidates twice.
 */
final class ExpandUnitGroupJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    /** The lock ends by itself, so a job lost before it ran does not block the group for ever. */
    public int $uniqueFor = 3600;

    public function __construct(
        public readonly string $iscoCode,
        public readonly ?string $provider = null,
        public readonly ?string $model = null,
        public readonly bool $force = false,
    ) {}

    public function uniqueId(): string
    {
        return $this->iscoCode;
    }

    public function handle(ExpandUnitGroup $expand): void
    {
        $result = $expand->handle($this->iscoCode, $this->provider, $this->model, $this->force);

        // Nothing was written. Without this line the job looks fine and the group silently stays empty.
        if (in_array($result->status, [ExpansionStatus::Failed, ExpansionStatus::Rejected], true)) {
            Log::warning('Unit group expansion wrote nothing.', [
                'isco_code' => $this->iscoCode,
                'status' => $result->status->value,
                'ai_run_id' => $result->runId,
                'error' => $result->error,
            ]);
        }
    }
}
