<?php

namespace App\Modules\Taxonomy\Data;

use App\Modules\AiPipeline\Data\CostEstimate;

final readonly class ExpansionPlan
{
    /**
     * @param  list<string>  $groups  the unit groups, in code order
     * @param  CostEstimate  $estimate  only the groups that need a model call
     * @param  int  $reused  groups whose earlier run is reused, with no call
     */
    public function __construct(
        public array $groups,
        public CostEstimate $estimate,
        public int $reused,
        public bool $queued,
    ) {}
}
