<?php

namespace App\Modules\Community\Queries;

use App\Modules\Community\Data\DiscussionPostData;
use App\Modules\Community\Data\DiscussionThreadData;
use App\Modules\Community\Models\DiscussionPost;
use App\Modules\Community\Models\DiscussionThread;
use Carbon\CarbonInterface;

/**
 * Reads talk pages (PLAN M19): an occupation's threads, a thread's posts, and recent posts for the
 * moderators' list. Hidden rows are included only when asked for; presenters hide hidden bodies.
 */
final class Discussions
{
    /**
     * Newest activity first.
     *
     * @return list<DiscussionThreadData>
     */
    public function threads(int $occupationId, bool $withHidden = false): array
    {
        return array_values(DiscussionThread::query()
            ->where('occupation_id', $occupationId)
            ->when(! $withHidden, fn ($query) => $query->whereNull('hidden_at'))
            ->orderByDesc('last_posted_at')
            ->orderByDesc('id')
            ->limit(200)
            ->get()
            ->map(fn (DiscussionThread $thread): DiscussionThreadData => $this->threadData($thread))
            ->all());
    }

    public function visibleThreadCount(int $occupationId): int
    {
        return DiscussionThread::query()->where('occupation_id', $occupationId)->whereNull('hidden_at')->count();
    }

    public function thread(int $id): ?DiscussionThreadData
    {
        $thread = DiscussionThread::query()->find($id);

        return $thread === null ? null : $this->threadData($thread);
    }

    /**
     * Oldest first, hidden ones included (they keep their place in the history).
     *
     * @return list<DiscussionPostData>
     */
    public function posts(int $threadId): array
    {
        return array_values(DiscussionPost::query()
            ->where('discussion_thread_id', $threadId)
            ->orderBy('id')
            ->get()
            ->map(fn (DiscussionPost $post): DiscussionPostData => $this->post($post))
            ->all());
    }

    public function findPost(int $id): ?DiscussionPostData
    {
        $post = DiscussionPost::query()->find($id);

        return $post === null ? null : $this->post($post);
    }

    /**
     * Posts that are not hidden, written since a time, flagged ones first, then newest first.
     *
     * @return list<array{post: DiscussionPostData, thread: DiscussionThreadData}>
     */
    public function recentPosts(CarbonInterface $since, int $limit = 100): array
    {
        $posts = DiscussionPost::query()
            ->with('thread')
            ->whereNull('hidden_at')
            ->whereHas('thread', fn ($query) => $query->whereNull('hidden_at'))
            ->where('created_at', '>=', $since)
            ->orderByRaw("CASE WHEN flags = '[]'::jsonb THEN 1 ELSE 0 END")
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        $recent = [];

        foreach ($posts as $post) {
            /** @var DiscussionThread $thread */
            $thread = $post->thread;
            $recent[] = ['post' => $this->post($post), 'thread' => $this->threadData($thread)];
        }

        return $recent;
    }

    /**
     * How many posts a person wrote since a time, for the daily limit (D68).
     */
    public function postsSince(int $userId, CarbonInterface $since): int
    {
        return DiscussionPost::query()->where('user_id', $userId)->where('created_at', '>=', $since)->count();
    }

    private function threadData(DiscussionThread $thread): DiscussionThreadData
    {
        return new DiscussionThreadData(
            id: $thread->id,
            occupationId: $thread->occupation_id,
            fieldKey: $thread->field_key,
            title: $thread->title,
            locale: $thread->locale,
            userId: $thread->user_id,
            postsCount: $thread->posts_count,
            lastPostedAt: $thread->last_posted_at,
            hidden: $thread->hidden_at !== null,
            createdAt: $thread->created_at,
        );
    }

    private function post(DiscussionPost $post): DiscussionPostData
    {
        return new DiscussionPostData(
            id: $post->id,
            threadId: $post->discussion_thread_id,
            userId: $post->user_id,
            body: $post->body,
            locale: $post->locale,
            flags: $post->flags,
            hidden: $post->hidden_at !== null,
            createdAt: $post->created_at,
        );
    }
}
