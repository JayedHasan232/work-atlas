<?php

namespace App\Modules\Community\Data;

/**
 * What became of a suggestion: applied at once (the person's role allows it), or waiting for a review.
 */
final readonly class SuggestionResult
{
    public function __construct(
        public int $revisionId,
        public bool $applied,
    ) {}
}
