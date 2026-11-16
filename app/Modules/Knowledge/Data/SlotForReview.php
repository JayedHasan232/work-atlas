<?php

namespace App\Modules\Knowledge\Data;

use App\Modules\Knowledge\Enums\CrossCheck;
use App\Modules\Knowledge\Enums\FieldStatus;

/**
 * A value slot on a reviewer's desk (PLAN M18): the current value and the proposals that wait.
 */
final readonly class SlotForReview
{
    /**
     * @param  list<EditRecord>  $proposals  oldest first
     */
    public function __construct(
        public int $fieldValueId,
        public int $occupationId,
        public FieldDefinitionData $definition,
        public ?string $locale,
        public ?string $region,
        public FieldStatus $status,
        public int $priority,
        public ?CrossCheck $crossCheck,
        public ?int $currentRevisionId,
        public mixed $currentValue,
        public array $proposals,
    ) {}
}
