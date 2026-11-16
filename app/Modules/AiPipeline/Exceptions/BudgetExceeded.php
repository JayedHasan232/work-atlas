<?php

namespace App\Modules\AiPipeline\Exceptions;

use RuntimeException;

/**
 * The gateway refused to send requests: their estimated cost is above `atlas.ai.max_batch_usd`, or a
 * model has no price, so the cost can't be checked. Nothing reached a provider and no run was made.
 */
final class BudgetExceeded extends RuntimeException
{
    public static function overCap(float $estimate, float $cap): self
    {
        return new self(sprintf('The estimated cost, %.6f USD, is above the cap of %.6f USD.', $estimate, $cap));
    }

    /**
     * @param  list<string>  $models  "provider/model"
     */
    public static function unknownPrice(array $models): self
    {
        return new self('No price in atlas.ai.pricing for '.implode(', ', $models).", so the cost can't be checked against the cap.");
    }
}
