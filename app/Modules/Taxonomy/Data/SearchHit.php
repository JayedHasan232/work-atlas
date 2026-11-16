<?php

namespace App\Modules\Taxonomy\Data;

use App\Modules\Taxonomy\Enums\SearchMatch;

/**
 * One occupation found by search, with the name that matched the query (often an alias or a local
 * name, which the result shows: "also called নলমিস্ত্রি").
 */
final readonly class SearchHit
{
    public function __construct(
        public OccupationListItem $occupation,
        public string $matchedName,
        public string $matchedLocale,
        public SearchMatch $match,
    ) {}
}
