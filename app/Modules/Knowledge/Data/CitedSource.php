<?php

namespace App\Modules\Knowledge\Data;

/**
 * A source a current value cites, as a reader sees it (PLAN M13).
 */
final readonly class CitedSource
{
    public function __construct(
        public int $id,
        public string $url,
        public ?string $publisher,
        public ?string $title,
        public ?string $license,
        public string $accessedOn,
        public ?string $archiveUrl,
    ) {}
}
