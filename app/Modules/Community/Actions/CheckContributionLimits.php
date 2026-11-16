<?php

namespace App\Modules\Community\Actions;

use App\Models\User;
use App\Modules\Community\Enums\Role;
use App\Modules\Community\Exceptions\ContributionRejected;
use App\Modules\Knowledge\Queries\ContributionCredits;
use Illuminate\Support\Facades\Config;

/**
 * Limits for new accounts (PLAN M16, A15) and by trust level (M18, D68). A person may write a limited
 * number of changes a day: fewer while their account is new, more when trusted (`atlas.community`);
 * reviewers and above are not limited. A blocked person writes nothing (D69). A new account's
 * links (sources, or a URL in the comment) always wait for a review, even from a trusted person.
 */
final class CheckContributionLimits
{
    public function __construct(
        private readonly ContributionCredits $credits,
    ) {}

    public function handle(User $user): void
    {
        $this->ensureNotBlocked($user);

        if ($user->role->atLeast(Role::Reviewer)) {
            return;
        }

        $limit = match (true) {
            $this->isNew($user) => Config::integer('atlas.community.daily_changes_new_account'),
            $user->role === Role::Trusted => Config::integer('atlas.community.daily_changes_trusted'),
            default => Config::integer('atlas.community.daily_changes'),
        };

        if ($this->credits->writtenSince($user->id, now()->subDay()) >= $limit) {
            throw ContributionRejected::because('limit', "user {$user->id} reached the limit of {$limit} changes a day");
        }
    }

    public function ensureNotBlocked(User $user): void
    {
        if ($user->isBlocked()) {
            throw ContributionRejected::because('blocked', "user {$user->id} is blocked");
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
