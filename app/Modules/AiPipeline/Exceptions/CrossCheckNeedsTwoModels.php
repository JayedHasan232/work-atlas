<?php

namespace App\Modules\AiPipeline\Exceptions;

use InvalidArgumentException;

final class CrossCheckNeedsTwoModels extends InvalidArgumentException
{
    public static function same(string $provider, string $model): self
    {
        return new self("A cross-check needs two different models; both are {$provider}/{$model} (atlas.ai.tasks.wave1).");
    }
}
