<?php

namespace App\Modules\Taxonomy\Queries;

use App\Modules\Taxonomy\Data\OccupationIndexPage;
use App\Modules\Taxonomy\Data\OccupationListItem;
use App\Modules\Taxonomy\Enums\NameType;
use App\Modules\Taxonomy\Enums\PublishState;
use App\Modules\Taxonomy\Models\Occupation;
use App\Modules\Taxonomy\Models\OccupationName;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;

/**
 * Listed and published occupations for the public lists (PLAN M13): those in one ISCO group, and the
 * index, sorted by their primary name in the reader's language.
 */
final class PublicOccupations
{
    /** One public occupation, or null when it has no public page. */
    public function find(int $id): ?OccupationListItem
    {
        $occupation = Occupation::query()->whereKey($id)->whereIn('publish_state', PublishState::public())->first();

        return $occupation === null ? null : $this->items([$occupation])[0];
    }

    /**
     * The public ones among these occupations, in the order given.
     *
     * @param  list<int>  $ids
     * @return list<OccupationListItem>
     */
    public function findMany(array $ids): array
    {
        $occupations = Occupation::query()->whereKey($ids)->whereIn('publish_state', PublishState::public())->get()->keyBy('id');
        $ordered = array_values(array_filter(array_map(fn (int $id): ?Occupation => $occupations->get($id), $ids)));

        return $this->items($ordered);
    }

    /**
     * The slugs of the indexable occupations, for the sitemap.
     *
     * @return list<string>
     */
    public function indexableSlugs(): array
    {
        return array_values(array_map(strval(...), Occupation::query()
            ->whereIn('publish_state', PublishState::public())
            ->where('indexable', true)
            ->orderBy('slug')
            ->pluck('slug')
            ->all()));
    }

    /**
     * @return list<OccupationListItem>
     */
    public function inGroup(string $code, string $locale): array
    {
        $occupations = $this->sorted($locale)->where('occupations.isco_code', 'like', $code.'%')->get();

        return $this->items($occupations->all());
    }

    public function index(string $locale, int $page, int $perPage): OccupationIndexPage
    {
        $total = Occupation::query()->whereIn('publish_state', PublishState::public())->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $lastPage);

        $occupations = $this->sorted($locale)->forPage($page, $perPage)->get();

        return new OccupationIndexPage($this->items($occupations->all()), $total, $page, $lastPage);
    }

    /**
     * @return Builder<Occupation>
     */
    private function sorted(string $locale): Builder
    {
        return Occupation::query()
            ->select('occupations.*')
            ->leftJoin('occupation_names as sort_name', function (JoinClause $join) use ($locale): void {
                $join->on('sort_name.occupation_id', '=', 'occupations.id')
                    ->where('sort_name.locale', $locale)
                    ->where('sort_name.type', NameType::Primary->value);
            })
            ->whereIn('occupations.publish_state', PublishState::public())
            ->orderByRaw('sort_name.normalized_name asc nulls last')
            ->orderBy('occupations.slug');
    }

    /**
     * @param  array<int, Occupation>  $occupations
     * @return list<OccupationListItem>
     */
    private function items(array $occupations): array
    {
        $ids = array_map(fn (Occupation $occupation): int => $occupation->id, $occupations);
        $names = [];

        foreach (OccupationName::query()->whereIn('occupation_id', $ids)->where('type', NameType::Primary)->get() as $name) {
            $names[$name->occupation_id][$name->locale] = $name->name;
        }

        return array_values(array_map(fn (Occupation $occupation): OccupationListItem => new OccupationListItem(
            id: $occupation->id,
            slug: $occupation->slug,
            iscoCode: $occupation->isco_code,
            names: $names[$occupation->id] ?? [],
            indexable: $occupation->indexable,
        ), $occupations));
    }
}
