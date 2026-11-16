<?php

namespace App\Modules\Knowledge\Queries;

use App\Modules\Knowledge\Data\FieldDefinitionData;
use App\Modules\Knowledge\Data\MatchableValue;
use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Models\FieldDefinition;
use Illuminate\Support\Facades\DB;

/**
 * The current national values of a few fields for many occupations at once (PLAN M20), for the guided
 * filter. For each occupation and key it takes the newest definition version that has a value. A
 * sensitive field's value counts only once it is verified, so matching never reveals what the page
 * withholds.
 */
final class MatchableValues
{
    /**
     * The newest definition of each key, keyed by key. Unknown keys are left out.
     *
     * @param  list<string>  $keys
     * @return array<string, FieldDefinitionData>
     */
    public function definitions(array $keys): array
    {
        $definitions = [];

        foreach (FieldDefinition::query()->whereIn('key', $keys)->orderBy('version')->get() as $definition) {
            $definitions[$definition->key] = $definition->toData();
        }

        return $definitions;
    }

    /**
     * @param  list<int>  $occupationIds
     * @param  list<string>  $keys
     * @return array<int, array<string, MatchableValue>>
     */
    public function handle(array $occupationIds, array $keys): array
    {
        if ($occupationIds === [] || $keys === []) {
            return [];
        }

        $rows = DB::table('field_values')
            ->join('field_definitions', 'field_definitions.id', '=', 'field_values.field_definition_id')
            ->join('revisions', 'revisions.id', '=', 'field_values.current_revision_id')
            ->whereIn('field_values.occupation_id', $occupationIds)
            ->whereIn('field_definitions.key', $keys)
            ->whereNull('field_values.region')
            ->whereNull('field_values.locale')
            ->orderByDesc('field_definitions.version')
            ->get([
                'field_values.occupation_id',
                'field_definitions.key',
                'field_definitions.sensitive',
                'field_values.status',
                'revisions.new_value',
            ]);

        $values = [];

        foreach ($rows as $row) {
            $occupationId = (int) $row->occupation_id;
            $key = (string) $row->key;

            if (isset($values[$occupationId][$key])) {
                continue;
            }

            $status = FieldStatus::from((string) $row->status);
            $withheld = (bool) $row->sensitive && $status !== FieldStatus::Verified;
            $values[$occupationId][$key] = new MatchableValue(
                value: $withheld || $row->new_value === null ? null : json_decode((string) $row->new_value, true),
                status: $status,
            );
        }

        return $values;
    }
}
