<?php

namespace App\Modules\Taxonomy\Queries;

use App\Modules\Taxonomy\Exceptions\UnknownUnitGroup;
use App\Modules\Taxonomy\Models\IscoGroup;

/**
 * The ISCO-08 unit groups (level 4) to work on: the given codes, checked, or all of them.
 */
final class UnitGroupCodes
{
    /**
     * @param  list<string>|null  $codes  null for every imported unit group
     * @return list<string>
     */
    public function handle(?array $codes = null): array
    {
        $query = IscoGroup::query()->where('level', 4)->orderBy('code');

        if ($codes === null) {
            return array_values($query->pluck('code')->all());
        }

        $codes = array_values(array_unique(array_map(trim(...), $codes)));
        $found = $query->whereIn('code', $codes)->pluck('code')->all();

        foreach ($codes as $code) {
            if (! in_array($code, $found, true)) {
                throw UnknownUnitGroup::code($code);
            }
        }

        return array_values($found);
    }
}
