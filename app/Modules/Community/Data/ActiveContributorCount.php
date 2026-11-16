<?php

namespace App\Modules\Community\Data;

/**
 * How many people contributed in a period (PLAN M23, D80): all of them, and those outside the core
 * team (anyone who is not a maintainer, A13).
 */
final readonly class ActiveContributorCount
{
    public function __construct(
        public int $all,
        public int $outsideCoreTeam,
    ) {}
}
