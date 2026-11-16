<?php

namespace App\Modules\Taxonomy\Actions;

use App\Modules\AiPipeline\AiGateway;
use App\Modules\Taxonomy\Data\ExpansionPlan;
use App\Modules\Taxonomy\Jobs\ExpandUnitGroupJob;

/**
 * Plans, and unless it is a dry run queues, the expansion of several unit groups: one job per group.
 * The cost estimate covers only the groups that need a model call (an earlier run with the same input
 * is reused). Before anything is queued, the whole set is checked against the AI budget cap, so the
 * cap holds for the run, not only for each call; a refused set queues nothing (BudgetExceeded).
 */
final class QueueUnitGroupExpansion
{
    public function __construct(
        private readonly AiGateway $gateway,
        private readonly BuildExpansionRequest $buildRequest,
    ) {}

    /**
     * @param  list<string>  $iscoCodes  unit groups, already checked (Queries\UnitGroupCodes)
     */
    public function handle(array $iscoCodes, ?string $provider = null, ?string $model = null, bool $force = false, bool $dryRun = false): ExpansionPlan
    {
        $calls = [];

        foreach ($iscoCodes as $code) {
            $request = $this->buildRequest->handle($code, $provider, $model);

            if ($force || $this->gateway->previousSuccess($request) === null) {
                $calls[] = $request;
            }
        }

        $estimate = $dryRun ? $this->gateway->estimate($calls) : $this->gateway->checkBudget($calls);

        if (! $dryRun) {
            foreach ($iscoCodes as $code) {
                ExpandUnitGroupJob::dispatch($code, $provider, $model, $force);
            }
        }

        return new ExpansionPlan($iscoCodes, $estimate, count($iscoCodes) - count($calls), ! $dryRun);
    }
}
