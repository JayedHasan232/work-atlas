<?php

namespace App\Modules\Discovery\Data;

/**
 * The guided filter's result (PLAN M20): the best matches, how many occupations matched in all,
 * and how many were checked.
 */
final readonly class MatchResult
{
    /**
     * @param  list<OccupationMatch>  $matches
     */
    public function __construct(
        public array $matches,
        public int $total,
        public int $checked,
    ) {}
}
