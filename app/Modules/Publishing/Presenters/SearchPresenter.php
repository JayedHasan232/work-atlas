<?php

namespace App\Modules\Publishing\Presenters;

use App\Modules\Taxonomy\Queries\SearchOccupations;
use Illuminate\Support\Facades\Config;

/**
 * The search page (PLAN M15): occupations whose names match the query, each with the name that
 * matched when it is not the one shown (an alias or a local name).
 */
final class SearchPresenter
{
    /** Longer queries are cut; no name is this long. */
    public const int MAX_QUERY_LENGTH = 100;

    public function __construct(
        private readonly SearchOccupations $search,
        private readonly OccupationLinks $links,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function present(string $query, string $locale): array
    {
        $query = mb_substr(trim($query), 0, self::MAX_QUERY_LENGTH);
        $results = [];

        foreach ($query === '' ? [] : $this->search->handle($query, $locale, Config::integer('atlas.search.per_page')) as $hit) {
            $link = $this->links->link($hit->occupation, $locale);
            $results[] = $link + [
                'matchedName' => $hit->matchedName === $link['name'] ? null : $hit->matchedName,
                'matchedLocale' => $hit->matchedLocale,
                'match' => $hit->match->value,
            ];
        }

        return ['query' => $query, 'results' => $results];
    }
}
