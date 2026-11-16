<?php

namespace App\Modules\Knowledge\Data;

use App\Modules\Knowledge\Enums\CrossCheck;
use App\Modules\Knowledge\Enums\FieldStatus;

/**
 * One value slot in the review queue, with its current value and the pending proposals' values.
 */
final readonly class QueueItem
{
    /**
     * @param  list<mixed>  $proposedValues
     */
    public function __construct(
        public int $fieldValueId,
        public int $occupationId,
        public string $fieldKey,
        public ?string $locale,
        public ?string $region,
        public FieldStatus $status,
        public int $priority,
        public ?CrossCheck $crossCheck,
        public mixed $currentValue,
        public array $proposedValues,
    ) {}
}
