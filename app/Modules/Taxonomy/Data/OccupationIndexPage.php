<?php

namespace App\Modules\Taxonomy\Data;

/**
 * One page of the occupation index.
 */
final readonly class OccupationIndexPage
{
    /**
     * @param  list<OccupationListItem>  $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $lastPage,
    ) {}
}
