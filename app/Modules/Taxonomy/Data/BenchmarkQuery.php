<?php

namespace App\Modules\Taxonomy\Data;

/**
 * One benchmark query and how search did on it: the rank of the expected occupation (1 = first),
 * or null when it was not in the results, and the time the search took.
 */
final readonly class BenchmarkQuery
{
    public function __construct(
        public string $query,
        public string $locale,
        public string $expectedSlug,
        public bool $expectedExists,
        public ?int $rank,
        public float $milliseconds,
    ) {}

    public function foundWithin(int $rank): bool
    {
        return $this->rank !== null && $this->rank <= $rank;
    }
}
