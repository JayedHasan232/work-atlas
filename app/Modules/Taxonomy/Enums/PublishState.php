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
}
