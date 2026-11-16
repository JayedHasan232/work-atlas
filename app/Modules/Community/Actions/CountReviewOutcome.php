<?php

namespace App\Modules\Community\Actions;

use App\Models\User;

/**
 * Counts a reviewed proposal on its author's trust counters. Knowledge calls it inside the review's
 * transaction, so the count and the review are saved together. An accepted proposal can make a
 * contributor trusted (PLAN M17, A11c).
 */
final class CountReviewOutcome
{
    public function __construct(
        private readonly PromoteTrustedContributors $promote,
    ) {}

    public function handle(int $authorId, bool $accepted): void
    {
        User::query()
            ->whereKey($authorId)
            ->increment($accepted ? 'accepted_proposals_count' : 'rejected_proposals_count');

        if ($accepted) {
            $this->promote->handle([$authorId]);
        }
    }
}
