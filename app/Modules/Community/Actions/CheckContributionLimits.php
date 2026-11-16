<?php

namespace App\Modules\Community\Actions;

use App\Models\User;
use App\Modules\Community\Enums\Role;
use App\Modules\Community\Exceptions\ContributionRejected;
use App\Modules\Knowledge\Queries\ContributionCredits;
use Illuminate\Support\Facades\Config;

/**
 * Limits for new accounts (PLAN M16, A15). A person may write a limited number of changes a day, fewer
 * while their account is new (`atlas.community`); reviewers and above are not limited. A new account's
 * links (sources, or a URL in the comment) always wait for a review, even from a trusted person.
 */
final class CheckContributionLimits
{
    public function __construct(
        private readonly ContributionCredits $credits,
    ) {}

    public function handle(User $user): void
    {
        if ($user->role->atLeast(Role::Reviewer)) {
            return;
        }

        $limit = $this->isNew($user)
            ? Config::integer('atlas.community.daily_changes_new_account')
            : Config::integer('atlas.community.daily_changes');

        if ($this->credits->writtenSince($user->id, now()->subDay()) >= $limit) {
            throw ContributionRejected::because('limit', "user {$user->id} reached the limit of {$limit} changes a day");
        }
    }

    public function isNew(User $user): bool
    {
        return $user->created_at === null
            || $user->created_at->gt(now()->subDays(Config::integer('atlas.community.new_account_days')));
    }

    /**
     * Whether a new account's change carries links, so it waits for a review.
     *
     * @param  list<string|null>  $texts
     */
    public function linksNeedReview(User $user, bool $hasSources, array $texts): bool
    {
        if (! $this->isNew($user) || $user->role->atLeast(Role::Reviewer)) {
            return false;
        }

        foreach ($texts as $text) {
            if ($text !== null && preg_match('#https?://|www\.#i', $text) === 1) {
                return true;
            }
        }

        return $hasSources;
    }
}
