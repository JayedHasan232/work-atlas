<?php

namespace App\Modules\Knowledge\Exceptions;

final class ReviewNotAllowed extends KnowledgeRuleViolation
{
    public static function because(int $revisionId, string $reason): self
    {
        return new self("Revision {$revisionId} cannot be reviewed: {$reason}.");
    }
}
