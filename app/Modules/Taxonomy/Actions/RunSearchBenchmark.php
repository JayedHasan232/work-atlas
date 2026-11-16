<?php

namespace App\Modules\Taxonomy\Actions;

use App\Modules\Taxonomy\Data\BenchmarkQuery;
use App\Modules\Taxonomy\Data\BenchmarkResult;
use App\Modules\Taxonomy\Models\Occupation;
use App\Modules\Taxonomy\Queries\SearchOccupations;
use App\Support\Localization\Locales;
use InvalidArgumentException;
use SplFileObject;

/**
 * Runs the search benchmark (PLAN M15, D60) against the occupations in the database. The file is
 * a CSV with the header `query,expected_slug,locale,note`: a query people really typed or said
 * (from the H2 interviews), the occupation they meant, the language of the page they searched on,
 * and where the query came from. An expected occupation that is not in the database counts as a
 * miss, and the result says so.
 */
final class RunSearchBenchmark
{
    private const array HEADER = ['query', 'expected_slug', 'locale', 'note'];

    public function __construct(private readonly SearchOccupations $search) {}

    public function handle(string $path, int $depth = 3): BenchmarkResult
    {
        $queries = [];

        foreach ($this->rows($path) as $row) {
            $start = hrtime(true);
            $hits = $this->search->handle($row['query'], $row['locale'], $depth);
            $milliseconds = (hrtime(true) - $start) / 1e6;

            $rank = null;

            foreach ($hits as $index => $hit) {
                if ($hit->occupation->slug === $row['expected_slug']) {
                    $rank = $index + 1;
                    break;
                }
            }

            $queries[] = new BenchmarkQuery(
                query: $row['query'],
                locale: $row['locale'],
                expectedSlug: $row['expected_slug'],
                expectedExists: Occupation::query()->where('slug', $row['expected_slug'])->exists(),
                rank: $rank,
                milliseconds: $milliseconds,
            );
        }

        return new BenchmarkResult($queries);
    }

    /**
     * @return list<array{query: string, expected_slug: string, locale: string}>
     */
    private function rows(string $path): array
    {
        if (! is_file($path)) {
            throw new InvalidArgumentException("No benchmark file at {$path}.");
        }

        $file = new SplFileObject($path);
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::READ_AHEAD);
        $rows = [];
        $line = 0;

        foreach ($file as $fields) {
            $line++;

            if (! is_array($fields) || $fields === [null]) {
                continue;
            }

            $fields = array_map(fn (mixed $field): string => trim((string) $field), $fields);

            if ($line === 1) {
                if ($fields !== self::HEADER) {
                    throw new InvalidArgumentException('The benchmark header must be: '.implode(',', self::HEADER).'.');
                }

                continue;
            }

            if (count($fields) !== count(self::HEADER) || $fields[0] === '' || $fields[1] === '' || ! in_array($fields[2], Locales::codes(), true)) {
                throw new InvalidArgumentException("Line {$line} of the benchmark needs a query, an expected slug and a supported locale.");
            }

            $rows[] = ['query' => $fields[0], 'expected_slug' => $fields[1], 'locale' => $fields[2]];
        }

        return $rows;
    }
}
