<?php

namespace App\Modules\AiPipeline\Data;

final readonly class Wave1Plan
{
    /**
     * @param  list<int>  $occupationIds
     * @param  CostEstimate  $estimate  only the calls that are not reused
     * @param  int  $reused  calls answered by an earlier run, with no new call
     */
    public function __construct(
        public array $occupationIds,
        public CostEstimate $estimate,
        public int $reused,
        public bool $queued,
    ) {}
}
