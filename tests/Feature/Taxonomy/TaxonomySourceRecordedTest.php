<?php

use App\Modules\Knowledge\Models\Source;
use App\Modules\Taxonomy\Models\IscoGroup;

function importTaxonomy(?string $file = null, ?string $source = null): array
{
    return [
        '--file' => $file ?? taxonomyFixture(),
        '--source' => $source ?? taxonomyFixture('isco-subset.source.json'),
    ];
}

test('the command imports the groups and records the source once', function () {
    $this->artisan('atlas:taxonomy:import', importTaxonomy())
        ->expectsOutputToContain('11 created, 0 updated, 0 unchanged')
        ->expectsOutputToContain('not the complete ISCO-08 structure')
        ->assertSuccessful();

    $this->artisan('atlas:taxonomy:import', importTaxonomy())
        ->expectsOutputToContain('0 created, 0 updated, 11 unchanged')
        ->assertSuccessful();

    $source = Source::query()->sole();

    expect(IscoGroup::query()->count())->toBe(11)
        ->and($source->url)->toBe('https://source.example.test/isco-subset.csv')
        ->and($source->publisher)->toBe('Test publisher')
        ->and($source->title)->toBe('Test ISCO subset (placeholder titles, not ILO data)')
        ->and($source->license)->toBe('CC0-1.0')
        ->and($source->accessed_on?->toDateString())->toBe('2026-11-01')
        ->and($source->archive_url)->toBeNull();
});

test('a new download of the file is a new source row', function () {
    $this->artisan('atlas:taxonomy:import', importTaxonomy())->assertSuccessful();

    $later = json_decode((string) file_get_contents(taxonomyFixture('isco-subset.source.json')), true);
    $later['accessed_on'] = '2026-11-10';
    $this->artisan('atlas:taxonomy:import', importTaxonomy(source: temporaryFile((string) json_encode($later), 'json')))
        ->assertSuccessful();

    expect(Source::query()->orderBy('accessed_on')->pluck('accessed_on')->map->toDateString()->all())
        ->toBe(['2026-11-01', '2026-11-10']);
});

test('the command fails and writes nothing without a usable source file', function (Closure $source, string $message) {
    $this->artisan('atlas:taxonomy:import', importTaxonomy(source: $source()))
        ->expectsOutputToContain($message)
        ->assertFailed();

    expect(IscoGroup::query()->count())->toBe(0)
        ->and(Source::query()->count())->toBe(0);
})->with([
    'missing file' => [fn () => taxonomyFixture('missing.source.json'), 'does not exist'],
    'not JSON' => [fn () => temporaryFile('{url:', 'json'), 'is not valid JSON'],
    'not an object' => [fn () => temporaryFile('"text"', 'json'), 'must hold a JSON object'],
    'no license' => [fn () => temporaryFile((string) json_encode([
        'url' => 'https://source.example.test/a.csv', 'publisher' => 'P', 'title' => 'T', 'accessed_on' => '2026-11-01',
    ]), 'json'), 'needs "license"'],
    'not a web address' => [fn () => temporaryFile((string) json_encode([
        'url' => 'file:///etc/passwd', 'publisher' => 'P', 'title' => 'T', 'license' => 'CC0-1.0', 'accessed_on' => '2026-11-01',
    ]), 'json'), 'must start with http:// or https://'],
]);

test('the command fails and writes nothing when the CSV is missing', function () {
    $this->artisan('atlas:taxonomy:import', importTaxonomy(file: taxonomyFixture('missing.csv')))
        ->expectsOutputToContain('the file does not exist')
        ->assertFailed();

    expect(Source::query()->count())->toBe(0);
});
