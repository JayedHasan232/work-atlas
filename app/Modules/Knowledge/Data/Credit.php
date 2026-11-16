<?php

namespace App\Modules\Knowledge\Data;

/**
 * How many credited changes one person made (PLAN M16): on one occupation, or in total.
 */
final readonly class Credit
{
    public function __construct(
        public int $userId,
        public int $contributions,
    ) {}
}
