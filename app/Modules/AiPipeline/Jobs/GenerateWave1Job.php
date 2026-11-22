<?php

namespace App\Modules\AiPipeline\Jobs;

use App\Modules\AiPipeline\Actions\GenerateWave1;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Wave 1 for one occupation. One try: the gateway already retries a provider error, and a rerun of
 * the command reuses every run that succeeded. One job per occupation at a time: a command run twice
 * before the queue drains must not pay for, and write, the same occupation twice.
 */
final class GenerateWave1Job implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    /** The lock ends by itself, so a job lost before it ran does not block the occupation for ever. */
    public int $uniqueFor = 3600;

    public function __construct(
        public readonly int $occupationId,
        public readonly bool $force = false,
    ) {}

    public function uniqueId(): string
    {
        return (string) $this->occupationId;
    }

    public function handle(GenerateWave1 $generate): void
    {
        $generate->handle($this->occupationId, $this->force);
    }
}
