<?php

namespace App\Modules\Discovery\Data;

use App\Modules\Discovery\Enums\MatchOutcome;
use App\Modules\Knowledge\Enums\FieldStatus;

/**
 * One answer checked against one occupation (PLAN M20): the field it read, the value and its status
 * (the data status behind the reason), and how it fared.
 */
final readonly class Criterion
{
    public function __construct(
        public string $question,
        public string $answer,
        public string $field,
        public MatchOutcome $outcome,
        public mixed $value,
        public ?FieldStatus $status,
    ) {}
}
