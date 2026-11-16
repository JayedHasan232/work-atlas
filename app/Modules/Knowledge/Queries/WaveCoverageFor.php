<?php

namespace App\Modules\Knowledge\Queries;

use App\Modules\Knowledge\Data\WaveCoverage;
use App\Modules\Knowledge\Enums\FieldStatus;
use Illuminate\Support\Facades\DB;

/**
 * Counts a wave's slots, filled slots and supported slots for the given occupations (PLAN M23, D80),
 * for the Gate 1 dashboard. Per occupation and field key it takes the newest definition version that
 * has a value, like the guided filter does; a disputed value is filled but not supported.
 */
final class WaveCoverageFor
{
    private const int CHUNK = 5000;

    public function __construct(private readonly WaveFields $waveFields) {}

    /**
     * @param  list<int>  $occupationIds
     */
    public function handle(int $wave, array $occupationIds): WaveCoverage
    {
        $keys = array_map(fn ($field): string => $field->key, $this->waveFields->handle($wave));
        $statuses = [];

        foreach (array_chunk($occupationIds, self::CHUNK) as $chunk) {
            $rows = DB::table('field_values')
                ->join('field_definitions', 'field_definitions.id', '=', 'field_values.field_definition_id')
                ->where('field_definitions.wave', $wave)
                ->whereIn('field_definitions.key', $keys)
                ->whereIn('field_values.occupation_id', $chunk)
                ->whereNull('field_values.region')
                ->whereNull('field_values.locale')
                ->whereNotNull('field_values.current_revision_id')
                ->orderByDesc('field_definitions.version')
                ->get(['field_values.occupation_id', 'field_definitions.key', 'field_values.status']);

            foreach ($rows as $row) {
                $slot = data_get($row, 'occupation_id').'|'.data_get($row, 'key');
                $statuses[$slot] ??= FieldStatus::from(data_get($row, 'status'));
            }
        }

        $supported = array_filter($statuses, fn (FieldStatus $status): bool => in_array(
            $status,
            [FieldStatus::CommunitySupported, FieldStatus::Verified],
            true,
        ));

        return new WaveCoverage(
            slots: count($occupationIds) * count($keys),
            filled: count($statuses),
            supported: count($supported),
        );
    }
}
