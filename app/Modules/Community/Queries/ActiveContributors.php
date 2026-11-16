<?php

namespace App\Modules\Community\Queries;

use App\Models\User;
use App\Modules\Community\Data\ActiveContributorCount;
use App\Modules\Community\Enums\Role;
use App\Modules\Community\Models\DiscussionPost;
use App\Modules\Community\Models\VerificationAnswer;
use App\Modules\Knowledge\Queries\ContributionCredits;
use Carbon\CarbonInterface;

/**
 * The people active since a moment (PLAN M23, D80), for the Gate 1 dashboard: they wrote a change
 * (an edit or a proposal), answered a fact-check question or posted on a talk page. Each person counts
 * once, however much they did.
 */
final class ActiveContributors
{
    public function __construct(private readonly ContributionCredits $credits) {}

    public function handle(CarbonInterface $since): ActiveContributorCount
    {
        $ids = array_values(array_unique(array_merge(
            $this->credits->writersSince($since),
            array_map(intval(...), VerificationAnswer::query()->where('created_at', '>=', $since)->distinct()->pluck('user_id')->all()),
            array_map(intval(...), DiscussionPost::query()->where('created_at', '>=', $since)->distinct()->pluck('user_id')->all()),
        )));

        $outside = $ids === [] ? 0 : User::query()
            ->whereKey($ids)
            ->where('role', '!=', Role::Maintainer)
            ->count();

        return new ActiveContributorCount(all: count($ids), outsideCoreTeam: $outside);
    }
}
