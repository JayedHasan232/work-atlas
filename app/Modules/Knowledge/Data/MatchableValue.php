<?php

namespace App\Modules\Knowledge\Data;

use App\Modules\Knowledge\Enums\FieldStatus;

/**
 * The current national value of one field of one occupation, as the guided filter compares it
 * (PLAN M20). A null value means "not known".
 */
final readonly class MatchableValue
{
    public function __construct(
        public mixed $value,
        public FieldStatus $status,
    ) {}
}
