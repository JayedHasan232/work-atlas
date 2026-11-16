<?php

namespace App\Modules\AiPipeline\Actions;

use App\Modules\AiPipeline\AiGateway;
use App\Modules\AiPipeline\Data\AiResult;
use App\Modules\AiPipeline\Data\Wave1Result;
use App\Modules\AiPipeline\Enums\Wave1Status;
use App\Modules\Knowledge\Queries\RunHasRevisions;
use App\Modules\Knowledge\Queries\WaveFields;

/**
 * Wave 1 for one occupation (PLAN M12): the same prompt on model A and model B, then ApplyWave1.
 * An earlier succeeded run with the same input is reused with no new call (D45), so a rerun after a
 * failure pays only for what failed; when model A's run is already written, the occupation is
 * skipped. `force` makes new calls.
 */
final class GenerateWave1
{
    public function __construct(
        private readonly AiGateway $gateway,
        private readonly BuildWave1Requests $buildRequests,
        private readonly ApplyWave1 $apply,
        private readonly WaveFields $waveFields,
        private readonly RunHasRevisions $runHasRevisions,
    ) {}

    public function handle(int $occupationId, bool $force = false): Wave1Result
    {
        $fields = $this->waveFields->handle(1);
        $results = [];

        foreach ($this->buildRequests->handle($occupationId, $fields) as $request) {
            $result = ($force ? null : $this->gateway->previousSuccess($request)) ?? $this->gateway->complete($request);
            $results[] = $result;

            if (! $result->succeeded()) {
                return new Wave1Result($occupationId, Wave1Status::Failed, $this->runIds($results), error: $result->error);
            }
        }

        [$a, $b] = $results;

        if ($this->runHasRevisions->handle($a->runId)) {
            return new Wave1Result($occupationId, Wave1Status::Skipped, $this->runIds($results));
        }

        return $this->apply->handle($occupationId, $fields, $a, $b);
    }

    /**
     * @param  list<AiResult>  $results
     * @return list<int>
     */
    private function runIds(array $results): array
    {
        return array_map(fn (AiResult $result): int => $result->runId, $results);
    }
}
