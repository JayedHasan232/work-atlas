<?php

namespace App\Modules\Taxonomy\Exceptions;

use RuntimeException;

final class NamesFieldMissing extends RuntimeException
{
    public static function make(): self
    {
        return new self('The "names" field definition is not in the database; run atlas:fields:sync first.');
    }
}
