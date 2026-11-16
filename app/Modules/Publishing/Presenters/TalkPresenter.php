<?php

namespace App\Modules\Publishing\Presenters;

use App\Models\User;
use App\Modules\Community\Data\DiscussionPostData;
use App\Modules\Community\Data\DiscussionThreadData;
use App\Modules\Community\Queries\Discussions;
use App\Modules\Community\Queries\PublicContributors;
use App\Modules\Knowledge\Queries\FieldDefinitionByKey;
use App\Modules\Knowledge\Queries\OccupationFields;
use App\Modules\Taxonomy\Data\OccupationListItem;
use App\Support\Localization\LocalizedRoutes;
use Illuminate\Support\Facades\Config;

/**
 * The props of an occupation's talk pages (PLAN M19). The public sees that a post or thread was hidden,
 * never what it said; moderators see the hidden text and the promotional-text flags (D70). Authors
 * who chose anonymous credit show as "a contributor" (D63).
 */
final class TalkPresenter
{
    /** @var array<string, string> */
    private array $labels = [];

    public function __construct(
        private readonly Discussions $discussions,
        private readonly PublicContributors $contributors,
        private readonly OccupationFields $fields,
        private readonly FieldDefinitionByKey $definitions,
        private readonly OccupationLinks $links,
        private readonly FieldDisplay $display,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function index(OccupationListItem $occupation, ?User $viewer, string $locale, ?string $field): array
    {
        $moderator = $viewer?->canModerate() === true;
        $fields = $this->fieldOptions($occupation->id, $locale);
        $field = $field !== null && in_array($field, array_column($fields, 'key'), true) ? $field : null;
        $threads = array_values(array_filter(
            $this->discussions->threads($occupation->id, $moderator),
            fn (DiscussionThreadData $thread): bool => $field === null || $thread->fieldKey === $field,
        ));
        $authors = $this->authors(array_map(fn (DiscussionThreadData $thread): int => $thread->userId, $threads), $locale);

        return [
            'occupation' => $this->links->link($occupation, $locale),
            'threads' => array_map(fn (DiscussionThreadData $thread): array => [
                'id' => $thread->id,
                'title' => $thread->title,
                'titleLocale' => $thread->locale,
                'field' => $this->fieldLink($thread->fieldKey, $locale),
                'author' => $authors[$thread->userId] ?? null,
                'postsCount' => $thread->postsCount,
                'lastPostedAt' => $thread->lastPostedAt?->toIso8601String(),
                'hidden' => $thread->hidden,
                'url' => LocalizedRoutes::path('talk.show', ['slug' => $occupation->slug, 'thread' => $thread->id], $locale),
            ], $threads),
            'fields' => $fields,
            'selectedField' => $field,
            'viewer' => $this->viewer($viewer),
            'storeUrl' => LocalizedRoutes::path('talk.store', ['slug' => $occupation->slug], $locale),
        ];
    }

    /**
     * Null when the thread does not exist or belongs to another occupation.
     *
     * @return array<string, mixed>|null
     */
    public function thread(OccupationListItem $occupation, int $threadId, ?User $viewer, string $locale): ?array
    {
        $thread = $this->discussions->thread($threadId);

        if ($thread === null || $thread->occupationId !== $occupation->id) {
            return null;
        }

        $moderator = $viewer?->canModerate() === true;
        $showHidden = ! $thread->hidden || $moderator;
        $posts = $showHidden ? $this->discussions->posts($thread->id) : [];
        $authors = $this->authors([$thread->userId, ...array_map(fn (DiscussionPostData $post): int => $post->userId, $posts)], $locale);

        return [
            'occupation' => $this->links->link($occupation, $locale),
            'talkUrl' => LocalizedRoutes::path('talk.index', ['slug' => $occupation->slug], $locale),
            'thread' => [
                'id' => $thread->id,
                'title' => $showHidden ? $thread->title : null,
                'titleLocale' => $thread->locale,
                'field' => $this->fieldLink($thread->fieldKey, $locale),
                'author' => $showHidden ? ($authors[$thread->userId] ?? null) : null,
                'hidden' => $thread->hidden,
                'createdAt' => $thread->createdAt?->toIso8601String(),
                'hideUrl' => $moderator ? LocalizedRoutes::path('talk.threads.hide', ['thread' => $thread->id], $locale) : null,
                'showUrl' => $moderator ? LocalizedRoutes::path('talk.threads.show', ['thread' => $thread->id], $locale) : null,
            ],
            'posts' => array_map(fn (DiscussionPostData $post): array => [
                'id' => $post->id,
                'author' => $post->hidden && ! $moderator ? null : ($authors[$post->userId] ?? null),
                'body' => $post->hidden && ! $moderator ? null : $post->body,
                'locale' => $post->locale,
                'hidden' => $post->hidden,
                'createdAt' => $post->createdAt?->toIso8601String(),
                'flags' => $moderator ? $post->flags : [],
                'hideUrl' => $moderator ? LocalizedRoutes::path('talk.posts.hide', ['post' => $post->id], $locale) : null,
                'showUrl' => $moderator ? LocalizedRoutes::path('talk.posts.show', ['post' => $post->id], $locale) : null,
            ], $posts),
            'viewer' => $this->viewer($viewer),
            'replyUrl' => $thread->hidden ? null : LocalizedRoutes::path('talk.reply', ['slug' => $occupation->slug, 'thread' => $thread->id], $locale),
        ];
    }

    /**
     * @return array{signedIn: bool, canPost: bool, canModerate: bool}
     */
    private function viewer(?User $viewer): array
    {
        return [
            'signedIn' => $viewer !== null,
            'canPost' => $viewer !== null && $viewer->hasVerifiedEmail() && ! $viewer->isBlocked(),
            'canModerate' => $viewer?->canModerate() === true,
        ];
    }

    /**
     * Named authors keyed by user id; an anonymous one is missing.
     *
     * @param  list<int>  $ids
     * @return array<int, array{name: string, url: string}>
     */
    private function authors(array $ids, string $locale): array
    {
        $authors = [];

        foreach ($this->contributors->named(array_values(array_unique($ids))) as $id => $contributor) {
            $authors[$id] = [
                'name' => $contributor->name,
                'url' => LocalizedRoutes::path('contributors.show', ['id' => $contributor->id], $locale),
            ];
        }

        return $authors;
    }

    /**
     * The fields shown on the occupation page, for the "about a field" choice.
     *
     * @return list<array{key: string, label: string}>
     */
    private function fieldOptions(int $occupationId, string $locale): array
    {
        /** @var list<int> $waves */
        $waves = Config::array('atlas.publishing.page_waves');
        $options = [];

        foreach ($this->fields->handle($occupationId, $waves) as $slot) {
            $key = $slot->definition->key;
            $this->labels[$key] = $this->display->text($slot->definition->label, $locale) ?? $key;
            $options[$key] = ['key' => $key, 'label' => $this->labels[$key]];
        }

        return array_values($options);
    }

    /**
     * @return array{key: string, label: string}|null
     */
    private function fieldLink(?string $key, string $locale): ?array
    {
        if ($key === null) {
            return null;
        }

        if (! isset($this->labels[$key])) {
            $field = $this->definitions->latest($key);
            $this->labels[$key] = ($field === null ? null : $this->display->text($field->definition->label, $locale)) ?? $key;
        }

        return ['key' => $key, 'label' => $this->labels[$key]];
    }
}
