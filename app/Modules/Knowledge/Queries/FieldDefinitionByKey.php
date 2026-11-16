<?php

namespace App\Modules\Knowledge\Queries;

use App\Modules\Knowledge\Data\EditableField;
use App\Modules\Knowledge\Models\FieldDefinition;

/**
 * The id of a field definition by key: the latest version, or a given one. Null when there is none
 * (run `atlas:fields:sync`).
 */
final class FieldDefinitionByKey
{
    public function handle(string $key, ?int $version = null): ?int
    {
        $id = FieldDefinition::query()
            ->where('key', $key)
            ->when($version !== null, fn ($query) => $query->where('version', $version))
            ->orderByDesc('version')
            ->value('id');

        return is_int($id) ? $id : null;
    }

    /**
     * The newest version of a field with its definition, for a person's suggestion (PLAN M16).
     */
    public function latest(string $key): ?EditableField
    {
        $definition = FieldDefinition::query()->where('key', $key)->orderByDesc('version')->first();

        return $definition === null ? null : new EditableField($definition->id, $definition->toData());
    }
}
