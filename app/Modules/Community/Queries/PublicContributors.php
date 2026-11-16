<?php

namespace App\Modules\Community\Queries;

use App\Models\User;
use App\Modules\Community\Data\Contributor;

/**
 * The people who may be named in the public credit (PLAN M16): everyone except those who chose
 * anonymous credit in their settings. Nothing but the name and the join date leaves this query.
 */
final class PublicContributors
{
    /**
     * The named ones among these people, keyed by id. A missing id is anonymous.
     *
     * @param  list<int>  $ids
     * @return array<int, Contributor>
     */
    public function named(array $ids): array
    {
        $contributors = [];

        foreach (User::query()->whereKey($ids)->where('anonymous_credit', false)->get(['id', 'name', 'created_at']) as $user) {
            $contributors[$user->id] = $this->contributor($user);
        }

        return $contributors;
    }

    public function find(int $id): ?Contributor
    {
        $user = User::query()->whereKey($id)->where('anonymous_credit', false)->first(['id', 'name', 'created_at']);

        return $user === null ? null : $this->contributor($user);
    }

    private function contributor(User $user): Contributor
    {
        return new Contributor($user->id, $user->name, $user->created_at);
    }
}
