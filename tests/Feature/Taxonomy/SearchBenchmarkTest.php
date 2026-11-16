<?php

use App\Modules\Taxonomy\Actions\RunSearchBenchmark;

/*
 * PLAN M15: the benchmark runner measures Recall@3 and search time on a CSV of queries. The real
 * benchmark (≥ 50 queries people really used, from H2) is not in the repository yet; this test runs a
 * synthetic sample against test occupations.
 */

beforeEach(function () {
    wave1Setup();
    listedOccupation()->update(['slug' => 'sample-plumber']);
    listedOccupation([
        'bn' => [['name' => 'বিদ্যুৎ মিস্ত্রি', 'type' => 'primary']],
        'en' => [['name' => 'Electrician', 'type' => 'primary']],
    ])->update(['slug' => 'sample-electrician']);
    $this->sample = base_path('tests/Fixtures/search/benchmark-sample.csv');
});

it('measures recall and time per query', function () {
    $result = app(RunSearchBenchmark::class)->handle($this->sample);

    expect($result->count())->toBe(5)
        ->and($result->recallAt(3))->toBe(0.8)
        ->and($result->percentileMs(95))->toBeGreaterThan(0.0)
        ->and(array_map(fn ($query) => [$query->query, $query->expectedExists], $result->misses(3)))
        ->toBe([['রাজমিস্ত্রি', false]]);
});

it('reports the result and fails below the target', function () {
    $report = temporaryFile('', 'md');

    $this->artisan('atlas:search:benchmark', ['--file' => $this->sample, '--report' => $report, '--target' => 0.9])
        ->expectsOutputToContain('80.0%')
        ->assertFailed();

    expect(file_get_contents($report))
        ->toContain('| Recall@3 | 80.0% (target 90.0%) |')
        ->toContain('| রাজমিস্ত্রি | bn | `sample-mason` (not in the database) | - |');

    $this->artisan('atlas:search:benchmark', ['--file' => $this->sample, '--target' => 0.8])->assertSuccessful();
});

it('refuses a file with the wrong columns', function () {
    $this->artisan('atlas:search:benchmark', ['--file' => temporaryFile("query,slug\nx,y\n")])
        ->expectsOutputToContain('The benchmark header must be')
        ->assertFailed();
});

it('says when the benchmark file is missing', function () {
    $this->artisan('atlas:search:benchmark', ['--file' => 'tests/Fixtures/search/nothing-here.csv'])
        ->expectsOutputToContain('No benchmark file')
        ->assertFailed();
});
