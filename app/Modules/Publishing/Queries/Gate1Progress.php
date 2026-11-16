<?php

namespace App\Modules\Publishing\Queries;

use App\Modules\Community\Queries\ActiveContributors;
use App\Modules\Knowledge\Queries\WaveCoverageFor;
use App\Modules\Publishing\Data\Gate1Status;
use App\Modules\Taxonomy\Queries\ListedOccupationIds;

/**
 * The Gate 1 dashboard's numbers (PLAN M23, D80), computed from the database each time.
 */
final class Gate1Progress
{
    public const int ACTIVE_DAYS = 30;

    public function __construct(
        private readonly ListedOccupationIds $listed,
        private readonly WaveCoverageFor $coverage,
        private readonly ActiveContributors $contributors,
    ) {}

    public function handle(): Gate1Status
    {
        $occupationIds = $this->listed->handle();

        return new Gate1Status(
            listedOccupations: count($occupationIds),
            wave1: $this->coverage->handle(1, $occupationIds),
            activeContributors: $this->contributors->handle(now()->subDays(self::ACTIVE_DAYS)),
        );
    }
}
