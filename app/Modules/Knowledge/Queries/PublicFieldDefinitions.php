<?php

namespace App\Modules\Knowledge\Queries;

use App\Modules\Knowledge\Data\FieldDefinitionData;
use App\Modules\Knowledge\Models\FieldDefinition;

/**
 * The newest version of every field in the given waves, by wave and key: the fields the public
 * pages and the API describe (PLAN M21).
 */
final class PublicFieldDefinitions
{
    /**
     * @param  list<int>  $waves
     * @return list<FieldDefinitionData>
     */
    public function handle(array $waves): array
    {
        return array_values(FieldDefinition::query()
            ->whereIn('wave', $waves)
            ->orderBy('wave')
            ->orderBy('key')
            ->orderByDesc('version')
            ->get()
            ->unique('key')
            ->map(fn (FieldDefinition $definition): FieldDefinitionData => $definition->toData())
            ->all());
    }
}
