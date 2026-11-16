<?php

namespace App\Modules\Knowledge\Exceptions;

final class InvalidSource extends KnowledgeRuleViolation
{
    public static function because(string $reason): self
    {
        return new self("Invalid source: {$reason}.");
    }
}
