<?php

namespace App\Modules\Knowledge\Exceptions;

/**
 * The value, locale, region or confidence does not fit the field definition.
 */
final class InvalidFieldValue extends KnowledgeRuleViolation
{
    public static function because(string $fieldKey, string $reason): self
    {
        return new self("Invalid value for field \"{$fieldKey}\": {$reason}.");
    }
}
