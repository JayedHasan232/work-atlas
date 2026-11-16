<?php

namespace App\Modules\Taxonomy\Actions;

use App\Modules\Taxonomy\Enums\NameType;
use App\Modules\Taxonomy\Enums\PublishState;
use App\Modules\Taxonomy\Models\Occupation;
use App\Modules\Taxonomy\Models\OccupationName;

/**
 * Keeps `occupation_names` a copy of the current `names` value in Knowledge (D44). The Knowledge
 * value, with its revisions and status, is the record; this table is what search, slugs and the
 * duplicate detector read. It runs inside the Knowledge write's transaction (CurrentValueChanged), so
 * the two never disagree.
 *
 * Two names with the same search key keep the first (the primary comes first).
 */
final class SyncOccupationNames
{
    public const string FIELD_KEY = 'names';

    public function __construct(private readonly NormalizeName $normalize) {}

    /**
     * @param  mixed  $names  the stored name list (see Knowledge's ValidateFieldValue), or null for none
     */
    public function handle(int $occupationId, string $locale, mixed $names): void
    {
        OccupationName::query()->where('occupation_id', $occupationId)->where('locale', $locale)->delete();

        // A merged occupation's names live on as aliases of the one it was merged into (M11).
        if (Occupation::query()->whereKey($occupationId)->where('publish_state', PublishState::Merged)->exists()) {
            return;
        }

        $rows = [];

        foreach (is_array($names) ? $names : [] as $name) {
            if (! is_array($name) || ! is_string($name['name'] ?? null) || ! is_string($name['type'] ?? null)) {
                continue;
            }

            $key = $this->normalize->handle($name['name']);
            $rows[$key] ??= [
                'occupation_id' => $occupationId,
                'locale' => $locale,
                'name' => $name['name'],
                'normalized_name' => $key,
                'type' => NameType::from($name['type']),
                'region' => is_string($name['region'] ?? null) ? $name['region'] : null,
            ];
        }

        foreach ($rows as $row) {
            OccupationName::query()->create($row);
        }
    }
}
