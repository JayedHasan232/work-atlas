<?php

namespace App\Modules\AiPipeline\Data;

/**
 * What the gateway hands a driver. `id` is the ai_runs id, so batch results find their run.
 */
final readonly class CompletionRequest
{
    public function __construct(
        public string $id,
        public string $model,
        public string $prompt,
        public string $schemaJson,
        public int $maxOutputTokens,
        public float $temperature,
    ) {}
}
