<?php

namespace App\Modules\Knowledge\Enums;

/**
 * How far a field value has been checked (rule 3). AI output enters as ai_draft, or sourced with a source.
 */
enum FieldStatus: string
{
    case AiDraft = 'ai_draft';
    case Sourced = 'sourced';
    case CommunitySupported = 'community_supported';
    case Verified = 'verified';
    case Disputed = 'disputed';

    /**
     * The status changes a person may make with ChangeStatus. A value only moves up the ladder
     * (ai_draft → sourced → community_supported → verified) or to disputed; a disputed value goes back
     * up once it is checked again. ai_draft is never a target: only a new AI revision sets it.
     *
     * @return list<self>
     */
    public function allowedTargets(): array
    {
        return match ($this) {
            self::AiDraft => [self::Sourced, self::CommunitySupported, self::Verified, self::Disputed],
            self::Sourced => [self::CommunitySupported, self::Verified, self::Disputed],
            self::CommunitySupported => [self::Verified, self::Disputed],
            self::Verified => [self::Disputed],
            self::Disputed => [self::Sourced, self::CommunitySupported, self::Verified],
        };
    }

    public function canMoveTo(self $target): bool
    {
        return in_array($target, $this->allowedTargets(), true);
    }

    /**
     * Whether this status is at least as far up the ladder as $minimum. A disputed value is never
     * far enough, whatever the minimum (the index gate, PLAN D55).
     */
    public function atLeast(self $minimum): bool
    {
        $ladder = [self::AiDraft, self::Sourced, self::CommunitySupported, self::Verified];
        $position = array_search($this, $ladder, true);
        $required = array_search($minimum, $ladder, true);

        return $position !== false && $required !== false && $position >= $required;
    }
}
