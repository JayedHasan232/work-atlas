<?php

namespace App\Modules\AiPipeline\Data;

/**
 * A dry-run estimate before anything is sent: an upper-bound guess at the tokens and, when every model
 * has a price in `atlas.ai.pricing`, the cost. `usd` is null when a price is unknown; `unpriced` names
 * those models as "provider/model".
 */
final readonly class CostEstimate
{
    /**
     * @param  list<string>  $unpriced
     */
    public function __construct(
        public int $requests,
        public int $inputTokens,
        public int $outputTokens,
        public ?float $usd,
        public array $unpriced = [],
    ) {}
}
