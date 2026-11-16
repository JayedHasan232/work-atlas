<?php

use App\Modules\Knowledge\Actions\RecordSource;
use App\Modules\Knowledge\Data\SourceInput;
use App\Modules\Knowledge\Exceptions\InvalidSource;
use App\Modules\Knowledge\Models\Source;

test('the same source read on the same day is recorded once', function () {
    $record = app(RecordSource::class);

    $first = $record->handle(fixtureSource());
    $again = $record->handle(fixtureSource());
    $otherDay = $record->handle(fixtureSource('2026-11-05'));
    $otherLicense = $record->handle(new SourceInput('https://source.example.test/isco-subset.csv', '2026-11-01', license: 'CC-BY-4.0'));

    expect($again)->toBe($first)
        ->and($otherDay)->not->toBe($first)
        ->and($otherLicense)->not->toBe($first)
        ->and(Source::query()->count())->toBe(3);
});

test('a source needs a web address and a real access day that has passed', function (SourceInput $input, string $reason) {
    $this->travelTo(new DateTimeImmutable('2026-11-16 12:00', new DateTimeZone('Asia/Dhaka')));

    expect(fn () => app(RecordSource::class)->handle($input))
        ->toThrow(InvalidSource::class, $reason)
        ->and(Source::query()->count())->toBe(0);
})->with([
    'no scheme' => [new SourceInput('source.example.test/a.csv', '2026-11-01'), 'url must start with'],
    'ftp' => [new SourceInput('ftp://source.example.test/a.csv', '2026-11-01'), 'url must start with'],
    'space in url' => [new SourceInput('https://source.example.test/a b.csv', '2026-11-01'), 'url must start with'],
    'bad archive url' => [new SourceInput('https://source.example.test/a.csv', '2026-11-01', archiveUrl: 'web.archive.org/x'), 'archive URL must start with'],
    'day that does not exist' => [new SourceInput('https://source.example.test/a.csv', '2026-02-30'), 'YYYY-MM-DD'],
    'other format' => [new SourceInput('https://source.example.test/a.csv', '01/11/2026'), 'YYYY-MM-DD'],
    'tomorrow in Dhaka' => [new SourceInput('https://source.example.test/a.csv', '2026-11-17'), 'in the future'],
]);

test('today in Dhaka is a valid access day', function () {
    // 20:00 UTC on 16 November is already 17 November in Dhaka (UTC+6).
    $this->travelTo(new DateTimeImmutable('2026-11-16 20:00', new DateTimeZone('UTC')));

    expect(app(RecordSource::class)->handle(new SourceInput('https://source.example.test/a.csv', '2026-11-17')))
        ->toBeInt();
});
