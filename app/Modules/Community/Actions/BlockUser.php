<?php

namespace App\Modules\Community\Actions;

use App\Models\User;
use App\Modules\Community\Exceptions\ModerationNotAllowed;

/**
 * A maintainer blocks a person (PLAN M18, D69): they can still sign in and read, but every
 * contribution and moderation action is refused. Their earlier edits stay in the history; revert
 * them one by one from the patrol queue. A maintainer cannot block themself.
 */
final class BlockUser
{
    public function handle(int $userId, User $by, ?string $reason = null): void
    {
        $this->authorize($userId, $by);

        $reason = $reason === null ? null : trim($reason);

        User::query()->whereKey($userId)->update([
            'blocked_at' => now(),
            'blocked_by' => $by->id,
            'block_reason' => $reason === '' ? null : $reason,
        ]);
    }

    public function unblock(int $userId, User $by): void
    {
        $this->authorize($userId, $by);

        User::query()->whereKey($userId)->update(['blocked_at' => null, 'blocked_by' => null, 'block_reason' => null]);
    }

    private function authorize(int $userId, User $by): void
    {
        if (! $by->role->canUseAdmin() || $by->isBlocked()) {
            throw ModerationNotAllowed::because("user {$by->id} cannot block people");
        }

        if ($userId === $by->id) {
            throw ModerationNotAllowed::because('a maintainer cannot block themself');
        }
    }
}
