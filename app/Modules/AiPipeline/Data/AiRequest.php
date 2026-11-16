<?php

namespace App\Modules\AiPipeline\Data;

/**
 * What a caller asks the gateway for: a prompt (key and version), its values, and the model to run it
 * on. The same prompt runs on two models for a cross-check.
 */
final readonly class AiRequest
{
    /**
     * @param  array<string, string>  $values
     */
    public function __construct(
        public string $promptKey,
        public int $promptVersion,
        public array $values,
        public string $provider,
        public string $model,
    ) {}
}
