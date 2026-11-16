<?php

namespace App\Modules\Community\Data;

use App\Modules\Knowledge\Data\CheckableValue;

/**
 * The next micro-task for a person, with the value it asks about.
 */
final readonly class OpenTask
{
    public function __construct(
        public int $taskId,
        public CheckableValue $value,
    ) {}
}
