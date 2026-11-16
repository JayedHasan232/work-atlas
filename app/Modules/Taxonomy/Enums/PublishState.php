<?php

namespace App\Modules\Taxonomy\Enums;

/**
 * Where an occupation is in its life: proposed, on the public list, with a published page, or merged into another.
 */
enum PublishState: string
{
    case Candidate = 'candidate';
    case Listed = 'listed';
    case Published = 'published';
    case Merged = 'merged';

    // A candidate the list review turned down (D46). Kept, so a rerun does not propose it as new.
    case Rejected = 'rejected';

    /**
     * Listed and published occupations have a public page (PLAN M13); candidates, rejected and
     * merged ones do not.
     *
     * @return list<self>
     */
    public static function public(): array
    {
        return [self::Listed, self::Published];
    }

    public function isPublic(): bool
    {
        return in_array($this, self::public(), true);
    }
}
