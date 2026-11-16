<?php

namespace App\Modules\Publishing\Actions;

use App\Modules\Knowledge\Data\SlotValue;
use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Queries\OccupationFields;
use App\Modules\Publishing\IndexGate;
use App\Modules\Taxonomy\Actions\SetIndexable;
use App\Modules\Taxonomy\Queries\PublicOccupations;

/**
 * Recomputes whether one occupation page may be indexed (PLAN M14) and stores the answer on the
 * occupation. Returns the answer.
 */
final class EvaluateIndexGate
{
    public function __construct(
        private readonly IndexGate $gate,
        private readonly PublicOccupations $occupations,
        private readonly OccupationFields $fields,
        private readonly SetIndexable $setIndexable,
    ) {}

    public function handle(int $occupationId): bool
    {
        $indexable = $this->gate->enabled()
            && $this->occupations->find($occupationId) !== null
            && $this->gate->passes($this->statuses($occupationId));

        $this->setIndexable->handle($occupationId, $indexable);

        return $indexable;
    }

    /**
     * @return array<string, list<FieldStatus>>
     */
    private function statuses(int $occupationId): array
    {
        $statuses = [];

        foreach ($this->fields->handle($occupationId, $this->gate->waves()) as $slot) {
            $statuses[$slot->definition->key] = array_map(fn (SlotValue $value): FieldStatus => $value->status, $slot->values);
        }

        return $statuses;
    }
}
