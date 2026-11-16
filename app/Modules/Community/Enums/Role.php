<?php

namespace App\Modules\Community\Enums;

use App\Modules\Knowledge\Enums\FieldStatus;

/**
 * A person's role, lowest first. Every role can do what the roles below it can. The methods are the
 * role × action matrix (PLAN M8, D36); the Knowledge actions ask them before writing.
 *
 * - contributor: suggests changes (proposals), reports a problem (disputed).
 * - trusted: edits non-sensitive fields directly (patrolled later), reverts them.
 * - reviewer: reviews proposals, verifies values, including sensitive fields.
 * - expert: a reviewer with subject knowledge (health, legal sign-off once those waves exist).
 * - maintainer: runs the project; uses the admin panel and Horizon.
 */
enum Role: string
{
    case Contributor = 'contributor';
    case Trusted = 'trusted';
    case Reviewer = 'reviewer';
    case Expert = 'expert';
    case Maintainer = 'maintainer';

    public function rank(): int
    {
        return match ($this) {
            self::Contributor => 1,
            self::Trusted => 2,
            self::Reviewer => 3,
            self::Expert => 4,
            self::Maintainer => 5,
        };
    }

    public function atLeast(self $role): bool
    {
        return $this->rank() >= $role->rank();
    }

    /**
     * Whether a write becomes the current value at once instead of waiting for a review. Sensitive
     * fields (income, health, legal) are always reviewed first.
     */
    public function appliesDirectly(bool $sensitive): bool
    {
        return ! $sensitive && $this->atLeast(self::Trusted);
    }

    public function canReview(): bool
    {
        return $this->atLeast(self::Reviewer);
    }

    /**
     * Anyone may mark a value disputed (report a problem). Raising trust needs a trusted person on a
     * non-sensitive field and a reviewer otherwise; only reviewers verify.
     */
    public function canSetStatus(FieldStatus $target, bool $sensitive): bool
    {
        return match ($target) {
            FieldStatus::Disputed => true,
            FieldStatus::Sourced, FieldStatus::CommunitySupported => $this->atLeast($sensitive ? self::Reviewer : self::Trusted),
            FieldStatus::Verified => $this->atLeast(self::Reviewer),
            FieldStatus::AiDraft => false,
        };
    }

    /**
     * Undoing a verified or sensitive value needs a reviewer; other values a trusted person.
     */
    public function canRevert(bool $sensitive, FieldStatus $current): bool
    {
        return $this->atLeast($sensitive || $current === FieldStatus::Verified ? self::Reviewer : self::Trusted);
    }

    public function canUseAdmin(): bool
    {
        return $this === self::Maintainer;
    }
}
