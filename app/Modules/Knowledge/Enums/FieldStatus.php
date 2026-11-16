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
}
