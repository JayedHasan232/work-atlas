<?php

namespace App\Modules\Taxonomy\Exceptions;

use RuntimeException;

final class UnknownUnitGroup extends RuntimeException
{
    public static function code(string $code): self
    {
        return new self("{$code} is not an imported ISCO-08 unit group (4 digits); run atlas:taxonomy:import first.");
    }
}
