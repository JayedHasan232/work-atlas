<?php

namespace App\Modules\AiPipeline\Jobs;

use App\Modules\AiPipeline\AiGateway;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Collects a submitted batch. While the provider is still working the job puts itself back on the
 * queue (`atlas.ai.batch_poll_seconds`), for up to two days.
 */
final class PollAiBatch implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $batchId) {}

    public function handle(AiGateway $gateway): void
    {
        if (! $gateway->collectBatch($this->batchId)) {
            $this->release((int) config('atlas.ai.batch_poll_seconds', 60));
        }
    }

    public function retryUntil(): DateTimeInterface
    {
        return now()->addDays(2);
    }
}
