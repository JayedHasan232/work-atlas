<?php

use App\Modules\Taxonomy\Data\SearchHit;
use App\Modules\Taxonomy\Enums\PublishState;
use App\Modules\Taxonomy\Queries\SearchOccupations;

/*
 * Rule 10 and PLAN M15: search finds an occupation by any of its names, in Bangla or English,
 * including local and regional names, however they are spelled.
 */

beforeEach(function () {
    wave1Setup();
    $this->plumber = listedOccupation();
    $this->electrician = listedOccupation([
        'bn' => [['name' => 'বিদ্যুৎ মিস্ত্রি', 'type' => 'primary'], ['name' => 'ইলেকট্রিশিয়ান', 'type' => 'alias']],
        'en' => [['name' => 'Electrician', 'type' => 'primary']],
    ]);
});

/**
 * @return list<array{0: string, 1: string, 2: string}> slug, matched name, match
 */
function search(string $query, string $locale = 'bn'): array
{
    return array_map(
        fn (SearchHit $hit): array => [$hit->occupation->slug, $hit->matchedName, $hit->match->value],
        app(SearchOccupations::class)->handle($query, $locale),
    );
}

it('finds an occupation by its local name', function () {
    expect(search('নলমিস্ত্রি')[0])->toBe([$this->plumber->slug, 'নলমিস্ত্রি', 'exact']);
});

it('finds an occupation by its English name from a Bangla page, and the other way round', function () {
    expect(search('plumber')[0])->toBe([$this->plumber->slug, 'Plumber', 'exact'])
        ->and(search('ইলেকট্রিশিয়ান', 'en')[0])->toBe([$this->electrician->slug, 'ইলেকট্রিশিয়ান', 'exact']);
});

it('finds a name by the start of the name or of one of its words', function () {
    expect(search('plum')[0])->toBe([$this->plumber->slug, 'Plumber', 'prefix'])
        ->and(search('বিদ্যু')[0])->toBe([$this->electrician->slug, 'বিদ্যুৎ মিস্ত্রি', 'prefix'])
        ->and(array_column(search('মিস্ত্রি'), 0))->toContain($this->electrician->slug, $this->plumber->slug);
});

it('finds a name typed with another spelling', function (string $query) {
    expect(search($query)[0][0])->toBe($this->electrician->slug);
})->with(['বিদ্যুত মিস্ত্রী', 'বিদ্যুত্ মিস্ত্রি', 'বিদ্যুৎ  মিস্ত্রি।', 'ELECTRICIAN']);

it('finds a misspelled name by similarity', function () {
    expect(search('electrishan')[0] ?? null)->toBe([$this->electrician->slug, 'Electrician', 'similar']);
});

it('ranks an exact match before a prefix and a prefix before a similar name', function () {
    $plumbing = listedOccupation([
        'en' => [['name' => 'Plumber helper', 'type' => 'primary']],
    ]);

    expect(search('plumber', 'en'))->toBe([
        [$this->plumber->slug, 'Plumber', 'exact'],
        [$plumbing->slug, 'Plumber helper', 'prefix'],
    ]);
});

it('lists each occupation once, by its best name', function () {
    // Both occupations match মিস্ত্রি, each once, however many of its names match.
    expect(search('মিস্ত্রি'))->toHaveCount(2)
        ->and(search('নলমিস্ত্রি'))->toBe([
            [$this->plumber->slug, 'নলমিস্ত্রি', 'exact'],
            [$this->electrician->slug, 'বিদ্যুৎ মিস্ত্রি', 'similar'],
        ]);
});

it('finds only occupations with a public page', function (PublishState $state) {
    $this->plumber->update(['publish_state' => $state]);

    expect(array_column(search('নলমিস্ত্রি'), 0))->not->toContain($this->plumber->slug);
})->with([PublishState::Candidate, PublishState::Rejected]);

it('finds nothing for an empty query', function (string $query) {
    expect(search($query))->toBe([]);
})->with(['', '   ', '।']);
