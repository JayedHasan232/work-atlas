<?php

namespace App\Modules\Taxonomy\Queries;

use App\Modules\Taxonomy\Data\GroupCount;
use App\Modules\Taxonomy\Data\IscoGroupData;
use App\Modules\Taxonomy\Enums\PublishState;
use App\Modules\Taxonomy\Models\IscoGroup;
use App\Modules\Taxonomy\Models\Occupation;

/**
 * ISCO-08 groups for browsing (PLAN M13): one group, the path from its major group down to it, and
 * its child groups with the number of public occupations under each.
 */
final class IscoGroups
{
    public function find(string $code): ?IscoGroupData
    {
        $group = IscoGroup::query()->where('code', $code)->first();

        return $group === null ? null : $this->data($group);
    }

    /**
     * The group and its parents, major group first.
     *
     * @return list<IscoGroupData>
     */
    public function path(string $code): array
    {
        $codes = array_map(fn (int $length): string => substr($code, 0, $length), range(1, strlen($code)));

        return array_values(IscoGroup::query()->whereIn('code', $codes)->orderBy('level')->get()
            ->map(fn (IscoGroup $group): IscoGroupData => $this->data($group))
            ->all());
    }

    /**
     * The major groups (no parent given) or the children of one group, in code order.
     *
     * @return list<GroupCount>
     */
    public function children(?string $parentCode = null): array
    {
        $groups = IscoGroup::query()
            ->when(
                $parentCode === null,
                fn ($query) => $query->where('level', 1),
                fn ($query) => $query->where('parent_code', $parentCode),
            )
            ->orderBy('code')
            ->get();

        if ($groups->isEmpty()) {
            return [];
        }

        // A literal per level, so the same expression can be selected and grouped by.
        $prefix = match ($groups->first()->level) {
            1 => 'left(isco_code, 1)',
            2 => 'left(isco_code, 2)',
            3 => 'left(isco_code, 3)',
            default => 'isco_code',
        };

        $counts = [];

        foreach (Occupation::query()
            ->whereIn('publish_state', PublishState::public())
            ->when($parentCode !== null, fn ($query) => $query->where('isco_code', 'like', $parentCode.'%'))
            ->selectRaw("{$prefix} as code, count(*) as total")
            ->groupByRaw($prefix)
            ->toBase()
            ->get() as $row) {
            $counts[(string) $row->code] = (int) $row->total;
        }

        return array_values($groups
            ->map(fn (IscoGroup $group): GroupCount => new GroupCount($this->data($group), $counts[$group->code] ?? 0))
            ->all());
    }

    private function data(IscoGroup $group): IscoGroupData
    {
        return new IscoGroupData($group->code, $group->level, $group->title_en, $group->title_bn);
    }
}
