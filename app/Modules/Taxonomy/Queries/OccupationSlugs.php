<?php

namespace App\Modules\Taxonomy\Queries;

use App\Modules\Taxonomy\Models\Occupation;

final class OccupationSlugs
{
    /**
     * @param  list<int>  $occupationIds
     * @return array<int, string> slug by occupation id
     */
    public function handle(array $occupationIds): array
    {
        /** @var array<int, string> */
        return Occupation::query()->whereKey($occupationIds)->pluck('slug', 'id')->all();
    }
}
