<?php

namespace App\Modules\Knowledge\Exceptions;

/**
 * A field definition file that can't be used as a whole. Nothing is synced when one is thrown.
 */
final class InvalidFieldFile extends KnowledgeRuleViolation
{
    public static function because(string $file, string $reason): self
    {
        return new self("Invalid field file {$file}: {$reason}.");
    }
}
