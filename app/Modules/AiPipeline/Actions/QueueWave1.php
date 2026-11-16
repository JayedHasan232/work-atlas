<?php

namespace App\Modules\AiPipeline\Actions;

use App\Modules\AiPipeline\AiGateway;
use App\Modules\AiPipeline\Data\Wave1Plan;
use App\Modules\AiPipeline\Jobs\GenerateWave1Job;
use App\Modules\Knowledge\Queries\WaveFields;

/**
 * Plans Wave 1 for a set of occupations: two calls each, minus the ones an earlier run answers. The
 * whole set is checked against the budget cap once, before any job is queued (D45); a dry run only
 * estimates.
 */
final class QueueWave1
{
    public function __construct(
        private readonly AiGateway $gateway,
        private readonly BuildWave1Requests $buildRequests,
        private readonly WaveFields $waveFields,
    ) {}

    /**
     * @param  list<int>  $occupationIds  listed or published occupations (Taxonomy\Queries\ListedOccupationIds)
     */
    public function handle(array $occupationIds, bool $force = false, bool $dryRun = false): Wave1Plan
    {
        $fields = $this->waveFields->handle(1);
        $calls = [];
        $reused = 0;

        foreach ($occupationIds as $occupationId) {
            foreach ($this->buildRequests->handle($occupationId, $fields) as $request) {
                if ($force || $this->gateway->previousSuccess($request) === null) {
                    $calls[] = $request;
                } else {
                    $reused++;
                }
            }
        }

        $estimate = $dryRun ? $this->gateway->estimate($calls) : $this->gateway->checkBudget($calls);

        if (! $dryRun) {
            foreach ($occupationIds as $occupationId) {
                GenerateWave1Job::dispatch($occupationId, $force);
            }
        }

        return new Wave1Plan($occupationIds, $estimate, $reused, ! $dryRun);
    }
}
