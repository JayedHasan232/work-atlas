<?php

namespace App\Modules\Taxonomy\Actions;

use App\Modules\AiPipeline\AiGateway;
use App\Modules\Taxonomy\Data\ExpansionResult;
use App\Modules\Taxonomy\Enums\ExpansionStatus;
use App\Modules\Taxonomy\Models\Occupation;

/**
 * Expands one ISCO-08 unit group into candidate occupations (PLAN M10). Safe to run again: when an
 * earlier run with the same input succeeded, its output is used instead of a new call, and if that
 * run's occupations already exist (or it proposed none) nothing happens (D45). `force` always makes a
 * new call.
 */
final class ExpandUnitGroup
{
    public function __construct(
        private readonly AiGateway $gateway,
        private readonly BuildExpansionRequest $buildRequest,
        private readonly ApplyExpansion $apply,
    ) {}

    public function handle(string $iscoCode, ?string $provider = null, ?string $model = null, bool $force = false): ExpansionResult
    {
        $request = $this->buildRequest->handle($iscoCode, $provider, $model);
        $result = $force ? null : $this->gateway->previousSuccess($request);

        if ($result !== null) {
            $made = array_values(Occupation::query()->where('ai_run_id', $result->runId)->orderBy('id')->pluck('id')->all());

            if ($made !== [] || ($result->output['occupations'] ?? []) === []) {
                return new ExpansionResult($iscoCode, ExpansionStatus::Skipped, $result->runId, $made);
            }
        }

        $result ??= $this->gateway->complete($request);

        if (! $result->succeeded() || $result->output === null) {
            return new ExpansionResult($iscoCode, ExpansionStatus::Failed, $result->runId, error: $result->error);
        }

        return $this->apply->handle($iscoCode, $result->runId, $result->output);
    }
}
