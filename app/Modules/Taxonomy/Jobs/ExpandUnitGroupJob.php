<?php

namespace App\Modules\Taxonomy\Jobs;

use App\Modules\Taxonomy\Actions\ExpandUnitGroup;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Expands one unit group on the queue (every AI call runs in a job). It runs once: the gateway retries
 * provider errors itself, and a second attempt would make a second run.
 */
final class ExpandUnitGroupJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(
        public readonly string $iscoCode,
        public readonly ?string $provider = null,
        public readonly ?string $model = null,
        public readonly bool $force = false,
    ) {}

    public function handle(ExpandUnitGroup $expand): void
    {
        $expand->handle($this->iscoCode, $this->provider, $this->model, $this->force);
    }
}
