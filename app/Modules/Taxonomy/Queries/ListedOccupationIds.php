<?php

namespace App\Modules\Taxonomy\Queries;

use App\Modules\Taxonomy\Enums\PublishState;
use App\Modules\Taxonomy\Exceptions\UnknownOccupation;
use App\Modules\Taxonomy\Models\Occupation;

/**
 * The listed and published occupations, the ones a data wave covers (PLAN M12). Given slugs, each
 * must be one of them.
 */
final class ListedOccupationIds
{
    /**
     * @param  list<string>|null  $slugs
     * @return list<int>
     */
    public function handle(?array $slugs = null): array
    {
        $query = Occupation::query()->whereIn('publish_state', [PublishState::Listed, PublishState::Published]);

        if ($slugs === null) {
            return array_values(array_map(intval(...), $query->orderBy('id')->pluck('id')->all()));
        }

        $found = $query->whereIn('slug', $slugs)->pluck('id', 'slug');
        $missing = array_values(array_diff($slugs, $found->keys()->all()));

        if ($missing !== []) {
            throw UnknownOccupation::slugs($missing);
        }

        return array_values(array_map(intval(...), $found->sort()->all()));
    }
}
