<?php

namespace App\Modules\Taxonomy\Exceptions;

use RuntimeException;

final class UnknownOccupation extends RuntimeException
{
    /**
     * @param  list<string>  $slugs
     */
    public static function slugs(array $slugs): self
    {
        return new self('Not a listed or published occupation: '.implode(', ', $slugs).'.');
    }
}
