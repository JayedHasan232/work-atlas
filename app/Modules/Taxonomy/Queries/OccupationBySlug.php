<?php

namespace App\Modules\Taxonomy\Queries;

use App\Modules\Taxonomy\Data\OccupationAddress;
use App\Modules\Taxonomy\Enums\PublishState;
use App\Modules\Taxonomy\Models\Occupation;

/**
 * Finds an occupation by slug, and for a merged one the occupation it now lives on (D5), following
 * the merge chain (merges keep it one step; the loop guards older data).
 */
final class OccupationBySlug
{
    private const int MAX_STEPS = 10;

    public function handle(string $slug): ?OccupationAddress
    {
        $occupation = Occupation::query()->where('slug', $slug)->first();

        if ($occupation === null) {
            return null;
        }

        $target = $occupation;

        for ($step = 0; $target->publish_state === PublishState::Merged && $step < self::MAX_STEPS; $step++) {
            $next = Occupation::query()->find($target->merged_into_id);

            if ($next === null) {
                break;
            }

            $target = $next;
        }

        return new OccupationAddress($target->id, $slug, $target->slug, $target->publish_state);
    }
}
