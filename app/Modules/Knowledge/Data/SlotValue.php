<?php

namespace App\Modules\Knowledge\Data;

use App\Modules\Knowledge\Enums\FieldStatus;
use Carbon\CarbonInterface;

/**
 * The current value of one slot (all of Bangladesh), with its status and the sources of the revision
 * that set it. A null value means "not known".
 */
final readonly class SlotValue
{
    /**
     * @param  list<CitedSource>  $sources
     */
    public function __construct(
        public ?string $locale,
        public mixed $value,
        public FieldStatus $status,
        public ?CarbonInterface $changedAt,
        public array $sources,
    ) {}
}
