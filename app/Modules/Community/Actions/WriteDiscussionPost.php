<?php

namespace App\Modules\Community\Actions;

use App\Modules\Community\Models\DiscussionPost;
use App\Modules\Community\Models\DiscussionThread;

/**
 * Writes one post into a thread the caller has locked or just made, with its promotional-text flags (D67), and moves the
 * thread's counters. Used by StartDiscussion and ReplyToDiscussion inside their transactions.
 */
final class WriteDiscussionPost
{
    public function __construct(
        private readonly FlagPromotionalEdit $flags,
    ) {}

    /**
     * @param  list<string>  $alsoCheck  other text that travels with the post (a new thread's title)
     */
    public function handle(DiscussionThread $thread, int $userId, string $body, string $locale, array $alsoCheck = []): int
    {
        $body = trim($body);
        $post = DiscussionPost::query()->create([
            'discussion_thread_id' => $thread->id,
            'user_id' => $userId,
            'body' => $body,
            'locale' => $locale,
            'flags' => $this->flags->textFlags([...$alsoCheck, $body]),
        ]);

        $thread->update([
            'posts_count' => $thread->posts_count + 1,
            'last_posted_at' => $post->created_at,
        ]);

        return $post->id;
    }
}
