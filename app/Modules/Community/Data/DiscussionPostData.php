<?php

namespace App\Modules\Community\Data;

use Carbon\CarbonInterface;

/**
 * A discussion post as other modules read it (PLAN M19). Presenters decide who sees a hidden body.
 */
final readonly class DiscussionPostData
{
    /**
     * @param  list<array{reason: string, detail: string|null}>  $flags
     */
    public function __construct(
        public int $id,
        public int $threadId,
        public int $userId,
        public string $body,
        public string $locale,
        public array $flags,
        public bool $hidden,
        public ?CarbonInterface $createdAt,
    ) {}
}
