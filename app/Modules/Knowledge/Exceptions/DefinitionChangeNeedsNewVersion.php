<?php

namespace App\Modules\Knowledge\Exceptions;

/**
 * The meaning of a definition (type, options, anchors, wave) can't change once values use it: values
 * stay tied to the scale they were written against. A change of meaning is a new version.
 */
final class DefinitionChangeNeedsNewVersion extends KnowledgeRuleViolation
{
    /**
     * @param  list<string>  $columns
     */
    public static function for(string $key, int $version, array $columns): self
    {
        return new self(sprintf(
            'Field %s v%d already has values, so its %s cannot change. Add version %d instead.',
            $key,
            $version,
            implode(', ', $columns),
            $version + 1,
        ));
    }
}
