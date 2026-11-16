<?php

namespace App\Modules\Community\Data;

use Carbon\CarbonInterface;

/**
 * A discussion thread as other modules read it (PLAN M19).
 */
final readonly class DiscussionThreadData
{
    public function __construct(
        public int $id,
        public int $occupationId,
        public ?string $fieldKey,
        public string $title,
        public string $locale,
        public int $userId,
        public int $postsCount,
        public ?CarbonInterface $lastPostedAt,
        public bool $hidden,
        public ?CarbonInterface $createdAt,
    ) {}
}
