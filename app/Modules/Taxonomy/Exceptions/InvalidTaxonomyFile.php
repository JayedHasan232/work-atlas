<?php

namespace App\Modules\Taxonomy\Exceptions;

use RuntimeException;

/**
 * A taxonomy file that can't be imported as a whole. Nothing is written when one is thrown.
 */
final class InvalidTaxonomyFile extends RuntimeException
{
    public static function because(string $path, string $reason): self
    {
        return new self("Cannot import {$path}: {$reason}.");
    }
}
