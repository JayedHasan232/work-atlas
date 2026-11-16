<?php

namespace App\Modules\Community\Queries;

use App\Models\User;
use App\Modules\Community\Actions\CheckContributionLimits;

/**
 * The authors on moderation pages (PLAN M18): name, role, whether the account is new or blocked.
 * Reviewers see names even of people who chose anonymous credit; credit is a public matter, patrol
 * is not.
 */
final class PeopleForModeration
{
    public function __construct(
        private readonly CheckContributionLimits $limits,
    ) {}

    /**
     * @param  list<int>  $userIds
     * @return array<int, array{id: int, name: string, role: string, newAccount: bool, blocked: bool}>
     */
    public function handle(array $userIds): array
    {
        $people = [];

        foreach (User::query()->whereKey(array_values(array_unique($userIds)))->get(['id', 'name', 'role', 'created_at', 'blocked_at']) as $user) {
            $people[$user->id] = [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role->value,
                'newAccount' => $this->limits->isNew($user),
                'blocked' => $user->isBlocked(),
            ];
        }

        return $people;
    }
}
