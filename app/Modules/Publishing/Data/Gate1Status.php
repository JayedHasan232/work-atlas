<?php

namespace App\Modules\Publishing\Data;

use App\Modules\Community\Data\ActiveContributorCount;
use App\Modules\Knowledge\Data\WaveCoverage;

/**
 * The numbers behind Gate 1 → Phase 2 (PLAN "Gates", M23): listed occupations, how many of their Wave 1
 * values the community supports, and who contributed in the last 30 days.
 */
final readonly class Gate1Status
{
    public function __construct(
        public int $listedOccupations,
        public WaveCoverage $wave1,
        public ActiveContributorCount $activeContributors,
    ) {}
}
