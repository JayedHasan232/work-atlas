<?php

namespace App\Modules\AiPipeline\Data;

use App\Modules\AiPipeline\Enums\Wave1Status;

final readonly class Wave1Result
{
    /**
     * @param  list<int>  $runIds  model A's run, then model B's, as far as they got
     */
    public function __construct(
        public int $occupationId,
        public Wave1Status $status,
        public array $runIds = [],
        public int $agreed = 0,
        public int $disagreed = 0,
        public ?string $error = null,
    ) {}
}
