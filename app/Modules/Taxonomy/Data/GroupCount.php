<?php

namespace App\Modules\Taxonomy\Data;

/**
 * A group with the number of public occupations anywhere under it.
 */
final readonly class GroupCount
{
    public function __construct(
        public IscoGroupData $group,
        public int $occupations,
    ) {}
}
