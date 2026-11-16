<?php

namespace App\Modules\Community\Data;

/**
 * A person opens a thread on an occupation's talk page (PLAN M19).
 */
final readonly class NewDiscussion
{
    public function __construct(
        public int $userId,
        public int $occupationId,
        public ?string $fieldKey,
        public string $title,
        public string $body,
        public string $locale,
    ) {}
}
