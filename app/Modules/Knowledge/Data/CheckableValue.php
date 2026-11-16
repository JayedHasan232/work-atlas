<?php

namespace App\Modules\Knowledge\Data;

use App\Modules\Knowledge\Enums\FieldStatus;

/**
 * A verifiable value with what a person needs to answer the question about it: the field and the
 * value of the revision being checked.
 */
final readonly class CheckableValue
{
    public function __construct(
        public int $fieldValueId,
        public int $revisionId,
        public int $occupationId,
        public FieldDefinitionData $definition,
        public mixed $value,
        public ?string $locale,
        public FieldStatus $status,
    ) {}
}
