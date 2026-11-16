<?php

namespace App\Console\Commands;

use App\Modules\Taxonomy\Actions\RunSearchBenchmark;
use App\Modules\Taxonomy\Data\BenchmarkQuery;
use App\Modules\Taxonomy\Data\BenchmarkResult;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;

/**
 * PLAN M15: run the search benchmark against the occupations in the database and print Recall@3
 * and the search times; `--report` also writes them as Markdown. Fails when Recall@3 is below the
 * target (proposed 90%, to be agreed). Reads only.
 */
final class SearchBenchmarkCommand extends Command
{
    protected $signature = 'atlas:search:benchmark
        {--file=tests/Fixtures/search-benchmark.csv : CSV with query,expected_slug,locale,note}
        {--report= : Write the result as Markdown to this path, e.g. docs/decisions/search-benchmark.md}
        {--target=0.9 : The Recall@3 the search must reach (0-1)}';

    protected $description = 'Measure search Recall@3 and speed on the benchmark queries';

    public function handle(RunSearchBenchmark $benchmark): int
    {
        $file = $this->pathOption('file');
        $target = (float) $this->option('target');

        try {
            $result = $benchmark->handle($file);
        } catch (InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($result->count() === 0) {
            $this->components->error('The benchmark has no queries.');

            return self::FAILURE;
        }

        $this->components->twoColumnDetail('Queries', (string) $result->count());
        $this->components->twoColumnDetail('Recall@1', $this->percent($result->recallAt(1)));
        $this->components->twoColumnDetail('Recall@3', $this->percent($result->recallAt(3)).' (target '.$this->percent($target).')');
        $this->components->twoColumnDetail('Time p50 / p95', sprintf('%.1f ms / %.1f ms', $result->percentileMs(50), $result->percentileMs(95)));

        $misses = $result->misses(3);

        if ($misses !== []) {
            $this->table(['Query', 'Locale', 'Expected', 'Rank'], array_map(fn (BenchmarkQuery $query): array => [
                $query->query, $query->locale, $query->expectedSlug.($query->expectedExists ? '' : ' (not in the database)'), $query->rank ?? '-',
            ], $misses));
        }

        $report = $this->option('report');

        if (is_string($report) && $report !== '') {
            File::put($this->absolute($report), $this->markdown($result, $file, $target));
            $this->components->info("Wrote {$report}.");
        }

        return $result->recallAt(3) >= $target ? self::SUCCESS : self::FAILURE;
    }

    private function markdown(BenchmarkResult $result, string $file, float $target): string
    {
        $lines = [
            '# Search benchmark',
            '',
            'Written by `php artisan atlas:search:benchmark` (PLAN M15). Do not edit by hand; run it again.',
            '',
            '| | |',
            '|---|---|',
            '| Run at (UTC) | '.now()->utc()->format('Y-m-d H:i').' |',
            '| Queries | '.$result->count().' (`'.str_replace(base_path().'/', '', $file).'`) |',
            '| Recall@1 | '.$this->percent($result->recallAt(1)).' |',
            '| Recall@3 | '.$this->percent($result->recallAt(3)).' (target '.$this->percent($target).') |',
            sprintf('| Time p50 / p95 | %.1f ms / %.1f ms |', $result->percentileMs(50), $result->percentileMs(95)),
            '',
            '## Not in the first three results',
            '',
        ];

        $misses = $result->misses(3);

        if ($misses === []) {
            $lines[] = 'None.';
        } else {
            $lines[] = '| Query | Locale | Expected | Rank |';
            $lines[] = '|---|---|---|---|';

            foreach ($misses as $query) {
                $lines[] = sprintf('| %s | %s | `%s`%s | %s |', str_replace('|', '\|', $query->query), $query->locale, $query->expectedSlug, $query->expectedExists ? '' : ' (not in the database)', $query->rank ?? '-');
            }
        }

        return implode("\n", $lines)."\n";
    }

    private function percent(float $share): string
    {
        return sprintf('%.1f%%', $share * 100);
    }

    private function pathOption(string $name): string
    {
        $value = $this->option($name);

        return $this->absolute(is_string($value) ? $value : '');
    }

    private function absolute(string $path): string
    {
        return str_starts_with($path, '/') ? $path : base_path($path);
    }
}
