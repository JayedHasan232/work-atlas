<?php

namespace App\Modules\Taxonomy\Data;

use App\Modules\Taxonomy\Enums\ExpansionStatus;

final readonly class ExpansionResult
{
    /**
     * @param  list<int>  $occupationIds  the candidate occupations the run made (also when skipped)
     */
    public function __construct(
        public string $iscoCode,
        public ExpansionStatus $status,
        public ?int $runId,
        public array $occupationIds = [],
        public ?string $error = null,
    ) {}
}
