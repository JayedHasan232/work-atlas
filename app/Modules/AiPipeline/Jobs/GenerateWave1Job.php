<?php

namespace App\Modules\AiPipeline\Jobs;

use App\Modules\AiPipeline\Actions\GenerateWave1;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Wave 1 for one occupation. One try: the gateway already retries a provider error, and a rerun of
 * the command reuses every run that succeeded.
 */
final class GenerateWave1Job implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(
        public readonly int $occupationId,
        public readonly bool $force = false,
    ) {}

    public function handle(GenerateWave1 $generate): void
    {
        $generate->handle($this->occupationId, $this->force);
    }
}
