<?php

namespace App\Modules\AiPipeline\Jobs;

use App\Modules\AiPipeline\AiGateway;
use App\Modules\AiPipeline\Data\AiRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Runs one AI request on the queue. The gateway retries provider errors itself, so the job runs once:
 * a second attempt would make a second run.
 */
final class RunAiCompletion implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public readonly AiRequest $request) {}

    public function handle(AiGateway $gateway): void
    {
        $gateway->complete($this->request);
    }
}
