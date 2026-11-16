<?php

namespace App\Modules\Knowledge\Data;

/**
 * A field definition with its id, so another module can build a RevisionInput for it.
 */
final readonly class EditableField
{
    public function __construct(
        public int $id,
        public FieldDefinitionData $definition,
    ) {}
}
