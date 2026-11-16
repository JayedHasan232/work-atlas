<?php

namespace App\Modules\Community\Actions;

use App\Models\User;
use App\Modules\Community\Exceptions\ModerationNotAllowed;
use App\Modules\Community\Models\DiscussionModeration;
use App\Modules\Community\Models\DiscussionPost;
use App\Modules\Community\Models\DiscussionThread;
use Closure;
use Illuminate\Support\Facades\DB;

/**
 * A moderator (reviewer and above, not blocked) hides or shows a thread or a post (PLAN M19, D70).
 * Nothing is deleted: the row stays, and each hide or show is logged with who did it and why.
 * Hiding something already hidden, or showing something visible, changes nothing.
 */
final class ModerateDiscussion
{
    public function hidePost(int $postId, User $moderator, ?string $reason = null): void
    {
        $this->set(fn (): DiscussionPost => DiscussionPost::query()->lockForUpdate()->findOrFail($postId), $moderator, true, $reason, 'discussion_post_id');
    }

    public function showPost(int $postId, User $moderator, ?string $reason = null): void
    {
        $this->set(fn (): DiscussionPost => DiscussionPost::query()->lockForUpdate()->findOrFail($postId), $moderator, false, $reason, 'discussion_post_id');
    }

    public function hideThread(int $threadId, User $moderator, ?string $reason = null): void
    {
        $this->set(fn (): DiscussionThread => DiscussionThread::query()->lockForUpdate()->findOrFail($threadId), $moderator, true, $reason, 'discussion_thread_id');
    }

    public function showThread(int $threadId, User $moderator, ?string $reason = null): void
    {
        $this->set(fn (): DiscussionThread => DiscussionThread::query()->lockForUpdate()->findOrFail($threadId), $moderator, false, $reason, 'discussion_thread_id');
    }

    /**
     * @param  Closure(): (DiscussionPost|DiscussionThread)  $find  locks and returns the row
     */
    private function set(Closure $find, User $moderator, bool $hide, ?string $reason, string $column): void
    {
        if (! $moderator->canModerate()) {
            throw ModerationNotAllowed::because("user {$moderator->id} cannot moderate talk pages");
        }

        $reason = $reason === null || trim($reason) === '' ? null : trim($reason);

        DB::transaction(function () use ($find, $moderator, $hide, $reason, $column): void {
            $row = $find();

            if (($row->hidden_at !== null) === $hide) {
                return;
            }

            $row->update(['hidden_at' => $hide ? now() : null, 'hidden_by' => $hide ? $moderator->id : null]);

            DiscussionModeration::query()->create([
                $column => $row->id,
                'action' => $hide ? 'hide' : 'show',
                'reason' => $reason,
                'user_id' => $moderator->id,
            ]);
        });
    }
}
