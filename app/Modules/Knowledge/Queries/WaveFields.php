<?php

namespace App\Modules\Knowledge\Queries;

use App\Modules\Knowledge\Data\WaveField;
use App\Modules\Knowledge\Models\FieldDefinition;

/**
 * The newest version of every field in a wave, ordered by key.
 */
final class WaveFields
{
    /**
     * @return list<WaveField>
     */
    public function handle(int $wave): array
    {
        return array_values(FieldDefinition::query()
            ->where('wave', $wave)
            ->orderBy('key')
            ->orderByDesc('version')
            ->get()
            ->unique('key')
            ->map(fn (FieldDefinition $definition): WaveField => new WaveField(
                id: $definition->id,
                key: $definition->key,
                version: $definition->version,
                type: $definition->type,
                label: $definition->label ?? [],
                help: $definition->help ?? [],
                options: $definition->options ?? [],
                optionLabels: $definition->option_labels ?? [],
                anchors: $definition->anchors ?? [],
            ))
            ->all());
    }
}
