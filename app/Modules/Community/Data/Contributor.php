<?php

namespace App\Modules\Community\Data;

use Carbon\CarbonInterface;

/**
 * A person as the public credit shows them (PLAN M16): their name and since when they contribute.
 * A person who chose anonymous credit has no public profile.
 */
final readonly class Contributor
{
    public function __construct(
        public int $id,
        public string $name,
        public ?CarbonInterface $since,
    ) {}
}
