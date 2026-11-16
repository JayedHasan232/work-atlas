<?php

namespace App\Modules\Taxonomy\Exceptions;

use DomainException;

/**
 * A list-review step (M11) that breaks a rule: the person's role, the occupation's state, or a merge
 * that would lose names. Nothing is written.
 */
final class ListReviewNotAllowed extends DomainException
{
    public static function because(string $reason): self
    {
        return new self($reason);
    }
}
