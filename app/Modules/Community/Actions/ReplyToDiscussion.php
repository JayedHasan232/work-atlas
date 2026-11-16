<?php

namespace App\Modules\Community\Actions;

use App\Models\User;
use App\Modules\Community\Exceptions\ContributionRejected;
use App\Modules\Community\Models\DiscussionThread;
use App\Modules\Taxonomy\Queries\PublicOccupations;
use Illuminate\Support\Facades\DB;

/**
 * Adds a reply to a thread (PLAN M19). A hidden thread takes no replies, and neither does a thread
 * whose occupation no longer has a public page. Same limits as changes (D68); blocked people cannot
 * post (D69).
 */
final class ReplyToDiscussion
{
    public function __construct(
        private readonly PublicOccupations $occupations,
        private readonly CheckContributionLimits $limits,
        private readonly WriteDiscussionPost $write,
    ) {}

    public function handle(int $occupationId, int $threadId, int $userId, string $body, string $locale): int
    {
        $user = User::query()->findOrFail($userId);
        $this->limits->handlePost($user);

        return DB::transaction(function () use ($occupationId, $threadId, $user, $body, $locale): int {
            $thread = DiscussionThread::query()->lockForUpdate()->find($threadId);

            if ($thread === null || $thread->occupation_id !== $occupationId || $this->occupations->find($occupationId) === null) {
                throw ContributionRejected::because('thread', "thread {$threadId} cannot be found");
            }

            if ($thread->hidden_at !== null) {
                throw ContributionRejected::because('hidden', "thread {$threadId} is hidden");
            }

            return $this->write->handle($thread, $user->id, $body, $locale);
        });
    }
}
