<?php

namespace App\Modules\AiPipeline\Data;

use App\Modules\AiPipeline\Enums\AiRunStatus;

/**
 * The outcome of one AI run. `output` is set only when the run succeeded, so a caller can't use
 * output that failed the schema.
 */
final readonly class AiResult
{
    /**
     * @param  array<array-key, mixed>|null  $output
     */
    public function __construct(
        public int $runId,
        public AiRunStatus $status,
        public ?array $output = null,
        public ?string $error = null,
    ) {}

    public function succeeded(): bool
    {
        return $this->status === AiRunStatus::Succeeded;
    }
}
