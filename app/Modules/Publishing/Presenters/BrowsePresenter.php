<?php

namespace App\Modules\Publishing\Presenters;

use App\Modules\Taxonomy\Data\GroupCount;
use App\Modules\Taxonomy\Data\IscoGroupData;
use App\Modules\Taxonomy\Data\OccupationListItem;
use App\Modules\Taxonomy\Queries\IscoGroups;
use App\Modules\Taxonomy\Queries\PublicOccupations;
use App\Support\Localization\LocalizedRoutes;
use Illuminate\Support\Facades\Config;

/**
 * The occupation index and the browse-by-ISCO-group pages (PLAN M13). Only listed and published
 * occupations appear.
 */
final class BrowsePresenter
{
    public function __construct(
        private readonly IscoGroups $groups,
        private readonly PublicOccupations $occupations,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function index(string $locale, int $page): array
    {
        $result = $this->occupations->index($locale, $page, Config::integer('atlas.publishing.per_page'));
        $pageUrl = fn (int $number): string => LocalizedRoutes::path('occupations.index', $number > 1 ? ['page' => $number] : [], $locale);

        return [
            'groups' => array_map(fn (GroupCount $count): array => $this->groupLink($count->group, $locale, $count->occupations), $this->groups->children()),
            'occupations' => array_map(fn (OccupationListItem $item): array => $this->item($item, $locale), $result->items),
            'pagination' => [
                'total' => $result->total,
                'page' => $result->page,
                'lastPage' => $result->lastPage,
                'previous' => $result->page > 1 ? $pageUrl($result->page - 1) : null,
                'next' => $result->page < $result->lastPage ? $pageUrl($result->page + 1) : null,
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null null when there is no such group
     */
    public function group(string $code, string $locale): ?array
    {
        $group = $this->groups->find($code);

        if ($group === null) {
            return null;
        }

        $isUnitGroup = $group->level === 4;

        return [
            'group' => $this->groupLink($group, $locale),
            'path' => array_map(fn (IscoGroupData $parent): array => $this->groupLink($parent, $locale), $this->groups->path($code)),
            'subgroups' => $isUnitGroup ? [] : array_map(
                fn (GroupCount $count): array => $this->groupLink($count->group, $locale, $count->occupations),
                $this->groups->children($code),
            ),
            'occupations' => $isUnitGroup ? array_map(
                fn (OccupationListItem $item): array => $this->item($item, $locale),
                $this->occupations->inGroup($code, $locale),
            ) : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function groupLink(IscoGroupData $group, string $locale, ?int $occupations = null): array
    {
        return [
            'code' => $group->code,
            'level' => $group->level,
            'title' => $group->title($locale),
            'titleLocale' => $group->titleLocale($locale),
            'url' => LocalizedRoutes::path('occupations.groups.show', ['code' => $group->code], $locale),
            'occupations' => $occupations,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function item(OccupationListItem $item, string $locale): array
    {
        $nameLocale = isset($item->names[$locale]) ? $locale : array_key_first($item->names);

        return [
            'slug' => $item->slug,
            'iscoCode' => $item->iscoCode,
            'name' => $nameLocale === null ? $item->slug : $item->names[$nameLocale],
            'nameLocale' => $nameLocale ?? 'en',
            'url' => LocalizedRoutes::path('occupations.show', ['slug' => $item->slug], $locale),
        ];
    }
}
