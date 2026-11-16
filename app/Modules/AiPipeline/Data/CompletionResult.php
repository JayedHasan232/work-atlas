<?php

namespace App\Modules\AiPipeline\Data;

/**
 * What a driver got back: the model's text, unparsed, and the tokens the provider counted.
 */
final readonly class CompletionResult
{
    public function __construct(
        public string $rawOutput,
        public ?int $inputTokens = null,
        public ?int $outputTokens = null,
    ) {}
}
