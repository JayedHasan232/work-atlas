<?php

namespace App\Modules\Knowledge\Exceptions;

/**
 * A `requires_source` field got a known value without a source, or a source id does not exist.
 */
final class SourceRequired extends KnowledgeRuleViolation
{
    public static function forField(string $fieldKey): self
    {
        return new self("Field \"{$fieldKey}\" needs at least one source for a known value.");
    }

    /**
     * @param  list<int>  $ids
     */
    public static function unknownSources(array $ids): self
    {
        return new self('Unknown source ids: '.implode(', ', $ids).'.');
    }
}
