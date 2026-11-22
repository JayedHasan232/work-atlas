<?php

namespace App\Modules\Community\Actions;

use App\Models\User;
use App\Modules\Community\Enums\Role;
use Illuminate\Support\Facades\Config;

/**
 * Makes a contributor trusted (PLAN M17, A11c, D65) once they have enough accepted contributions
 * (accepted proposals plus answers that agreed with a reached consensus) and their account is old
 * enough. Only contributors move; a role set by a maintainer is never lowered or changed here. Runs
 * after each counted contribution, and daily (`atlas:trust:promote`) for accounts that only aged.
 */
final class PromoteTrustedContributors
{
    public function __construct(
        private readonly ChangeRole $changeRole,
    ) {}

    /**
     * @param  list<int>|null  $userIds  null checks every contributor
     */
    public function handle(?array $userIds = null): int
    {
        if ($userIds === []) {
            return 0;
        }

        $users = User::query()
            ->where('role', Role::Contributor)
            ->whereNull('blocked_at')
            ->when($userIds !== null, fn ($query) => $query->whereKey($userIds))
            ->where('created_at', '<=', now()->subDays(Config::integer('atlas.community.trusted_after_days')))
            ->whereRaw('accepted_proposals_count + confirmed_answers_count >= ?', [Config::integer('atlas.community.trusted_after_contributions')])
            ->get(['id', 'email']);

        foreach ($users as $user) {
            $this->changeRole->handle($user->email, Role::Trusted);
        }

        return $users->count();
    }
}
