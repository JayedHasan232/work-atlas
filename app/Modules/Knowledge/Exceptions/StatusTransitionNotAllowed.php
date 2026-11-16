<?php

namespace App\Modules\Knowledge\Exceptions;

use App\Modules\Knowledge\Enums\FieldStatus;

final class StatusTransitionNotAllowed extends KnowledgeRuleViolation
{
    public static function between(FieldStatus $from, FieldStatus $to): self
    {
        return new self("A field value cannot move from {$from->value} to {$to->value}.");
    }

    public static function because(string $reason): self
    {
        return new self("Status change not allowed: {$reason}.");
    }
}
