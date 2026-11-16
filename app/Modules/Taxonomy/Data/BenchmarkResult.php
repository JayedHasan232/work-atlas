<?php

namespace App\Modules\Taxonomy\Data;

/**
 * The search benchmark (PLAN M15): Recall@1 and Recall@3 over the queries, and the search times.
 */
final readonly class BenchmarkResult
{
    /**
     * @param  list<BenchmarkQuery>  $queries
     */
    public function __construct(public array $queries) {}

    public function count(): int
    {
        return count($this->queries);
    }

    /** The share of queries whose expected occupation is in the first `$rank` results (0–1). */
    public function recallAt(int $rank): float
    {
        if ($this->queries === []) {
            return 0.0;
        }

        return count(array_filter($this->queries, fn (BenchmarkQuery $query): bool => $query->foundWithin($rank))) / count($this->queries);
    }

    /** A percentile of the search times in milliseconds (nearest rank). */
    public function percentileMs(int $percentile): float
    {
        $times = array_map(fn (BenchmarkQuery $query): float => $query->milliseconds, $this->queries);
        sort($times);

        if ($times === []) {
            return 0.0;
        }

        return $times[max(0, (int) ceil($percentile / 100 * count($times)) - 1)];
    }

    /** @return list<BenchmarkQuery> the queries whose expected occupation was not in the first `$rank` results */
    public function misses(int $rank): array
    {
        return array_values(array_filter($this->queries, fn (BenchmarkQuery $query): bool => ! $query->foundWithin($rank)));
    }
}
