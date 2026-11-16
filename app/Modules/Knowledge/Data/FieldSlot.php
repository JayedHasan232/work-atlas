<?php

namespace App\Modules\Knowledge\Data;

/**
 * One field of one occupation: its definition and its current values, one per language for a field
 * that has a language, at most one otherwise. No value yet means an empty list.
 */
final readonly class FieldSlot
{
    /**
     * @param  list<SlotValue>  $values
     */
    public function __construct(
        public FieldDefinitionData $definition,
        public array $values,
    ) {}
}
