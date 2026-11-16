<?php

namespace App\Modules\AiPipeline\Data;

/**
 * A submitted batch: the provider's batch id (null when the submission failed) and one run per request.
 */
final readonly class BatchSubmission
{
    /**
     * @param  list<int>  $runIds
     */
    public function __construct(
        public ?string $batchId,
        public array $runIds,
        public ?string $error = null,
    ) {}
}
