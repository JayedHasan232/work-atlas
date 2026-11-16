<?php

namespace App\Modules\Knowledge\Queries;

use App\Modules\Knowledge\Data\CitedSource;
use App\Modules\Knowledge\Data\FieldDefinitionData;
use App\Modules\Knowledge\Data\FieldSlot;
use App\Modules\Knowledge\Data\SlotValue;
use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Knowledge\Models\FieldValue;
use App\Modules\Knowledge\Models\Source;
use Illuminate\Support\Collection;

/**
 * Every field of the given waves for one occupation, in the order of the field files, with the
 * current national values (PLAN M13). For each key it uses the newest definition version that has a
 * value, else the newest version, so a new version does not hide the values of the old one before
 * they are generated again. Raw values: what a reader may see is decided in Publishing.
 */
final class OccupationFields
{
    /**
     * @param  list<int>  $waves
     * @return list<FieldSlot>
     */
    public function handle(int $occupationId, array $waves): array
    {
        $definitions = FieldDefinition::query()->whereIn('wave', $waves)->orderBy('id')->get();

        /** @var Collection<int, Collection<int, FieldValue>> $values */
        $values = FieldValue::query()
            ->where('occupation_id', $occupationId)
            ->whereIn('field_definition_id', $definitions->modelKeys())
            ->whereNull('region')
            ->whereNotNull('current_revision_id')
            ->with('currentRevision.sources')
            ->orderBy('locale')
            ->get()
            ->groupBy('field_definition_id');

        $slots = [];

        foreach ($definitions->groupBy('key') as $versions) {
            /** @var Collection<int, FieldDefinition> $versions */
            $newestFirst = $versions->sortByDesc('version');
            $chosen = $newestFirst->first(fn (FieldDefinition $definition): bool => $values->has($definition->id))
                ?? $newestFirst->firstOrFail();

            $slots[] = [
                'order' => [$chosen->wave, $versions->min('id')],
                'slot' => new FieldSlot(
                    definition: $this->definition($chosen),
                    values: array_values(($values->get($chosen->id) ?? collect())
                        ->map(fn (FieldValue $value): SlotValue => $this->value($value))
                        ->all()),
                ),
            ];
        }

        usort($slots, fn (array $a, array $b): int => $a['order'] <=> $b['order']);

        return array_column($slots, 'slot');
    }

    private function definition(FieldDefinition $definition): FieldDefinitionData
    {
        return new FieldDefinitionData(
            key: $definition->key,
            version: $definition->version,
            wave: $definition->wave,
            group: $definition->group,
            type: $definition->type,
            label: $definition->label ?? [],
            help: $definition->help ?? [],
            options: $definition->options,
            optionLabels: $definition->option_labels,
            anchors: $definition->anchors,
            sensitive: $definition->sensitive,
            requiresSource: $definition->requires_source,
        );
    }

    private function value(FieldValue $value): SlotValue
    {
        $revision = $value->currentRevision;

        return new SlotValue(
            locale: $value->locale,
            value: $revision?->new_value,
            status: $value->status,
            changedAt: $revision?->created_at,
            sources: array_values(($revision === null ? collect() : $revision->sources)
                ->sortBy('id')
                ->map(fn (Source $source): CitedSource => new CitedSource(
                    id: $source->id,
                    url: $source->url,
                    publisher: $source->publisher,
                    title: $source->title,
                    license: $source->license,
                    accessedOn: $source->accessed_on->toDateString(),
                    archiveUrl: $source->archive_url,
                ))
                ->all()),
        );
    }
}
