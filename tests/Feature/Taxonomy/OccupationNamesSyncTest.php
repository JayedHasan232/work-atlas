<?php

use App\Modules\Knowledge\Actions\RevertRevision;
use App\Modules\Knowledge\Actions\ReviewRevision;
use App\Modules\Knowledge\Enums\ReviewDecision;
use App\Modules\Knowledge\Exceptions\InvalidFieldValue;
use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Taxonomy\Actions\NormalizeName;
use App\Modules\Taxonomy\Enums\NameType;
use App\Modules\Taxonomy\Models\Occupation;
use App\Modules\Taxonomy\Models\OccupationName;

/*
 * occupation_names is a copy of the current `names` values (D44): it follows every direct write,
 * accepted proposal and revert, in the same transaction, and ignores pending proposals.
 */

/**
 * @return list<array{0: string, 1: string, 2: string, 3: string|null}> [locale, name, type, region]
 */
function namesOf(Occupation $occupation): array
{
    return OccupationName::query()->where('occupation_id', $occupation->id)->orderBy('locale')->orderBy('id')->get()
        ->map(fn (OccupationName $name) => [$name->locale, $name->name, $name->type->value, $name->region])->all();
}

beforeEach(function () {
    $this->field = FieldDefinition::factory()->names()->create();
    $this->occupation = Occupation::factory()->create();
});

it('copies an AI write of the names into occupation_names, per language', function () {
    recordValue($this->field, [['name' => 'প্লাম্বার', 'type' => 'primary'], ['name' => 'নলমিস্ত্রি', 'type' => 'local']], occupationId: $this->occupation->id, locale: 'bn');
    recordValue($this->field, [['name' => 'Plumber', 'type' => 'primary']], occupationId: $this->occupation->id, locale: 'en');

    expect(namesOf($this->occupation))->toBe([
        ['bn', 'প্লাম্বার', 'primary', null],
        ['bn', 'নলমিস্ত্রি', 'local', null],
        ['en', 'Plumber', 'primary', null],
    ]);
});

it('replaces the copy when the names change, keeping the other language', function () {
    recordValue($this->field, [['name' => 'Plumber', 'type' => 'primary']], occupationId: $this->occupation->id, locale: 'en');
    recordValue($this->field, [['name' => 'প্লাম্বার', 'type' => 'primary']], occupationId: $this->occupation->id, locale: 'bn');
    recordValue($this->field, [['name' => 'Pipe fitter', 'type' => 'primary'], ['name' => 'Plumber', 'type' => 'alias']], occupationId: $this->occupation->id, locale: 'en');

    expect(namesOf($this->occupation))->toBe([
        ['bn', 'প্লাম্বার', 'primary', null],
        ['en', 'Pipe fitter', 'primary', null],
        ['en', 'Plumber', 'alias', null],
    ]);
});

it('ignores a pending proposal and follows it once accepted', function () {
    recordValue($this->field, [['name' => 'Plumber', 'type' => 'primary']], occupationId: $this->occupation->id, locale: 'en');
    $proposal = recordValue($this->field, [['name' => 'Plumber', 'type' => 'primary'], ['name' => 'Pipe fixer', 'type' => 'alias']], personActor(), occupationId: $this->occupation->id, locale: 'en');

    expect(namesOf($this->occupation))->toHaveCount(1);

    app(ReviewRevision::class)->handle($proposal->id, reviewerActor(), ReviewDecision::Accepted);

    expect(namesOf($this->occupation))->toBe([['en', 'Plumber', 'primary', null], ['en', 'Pipe fixer', 'alias', null]]);
});

it('follows a revert', function () {
    recordValue($this->field, [['name' => 'Plumber', 'type' => 'primary']], occupationId: $this->occupation->id, locale: 'en');
    $second = recordValue($this->field, [['name' => 'Pipe fitter', 'type' => 'primary']], occupationId: $this->occupation->id, locale: 'en');

    app(RevertRevision::class)->handle($second->id, reviewerActor());

    expect(namesOf($this->occupation))->toBe([['en', 'Plumber', 'primary', null]]);
});

it('keeps regional names with their region and the first of two names with one search key', function () {
    recordValue($this->field, [
        ['name' => 'বিদ্যুৎ মিস্ত্রি', 'type' => 'primary'],
        ['name' => 'বিদ্যুৎ-মিস্ত্রি', 'type' => 'alias'],
        ['name' => 'কারেন্টের মিস্ত্রি', 'type' => 'regional', 'region' => 'chattogram'],
    ], occupationId: $this->occupation->id, locale: 'bn');

    expect(namesOf($this->occupation))->toBe([
        ['bn', 'বিদ্যুৎ মিস্ত্রি', 'primary', null],
        ['bn', 'কারেন্টের মিস্ত্রি', 'regional', 'chattogram'],
    ]);
});

it('writes neither the value nor the copy when the names are invalid', function () {
    recordValue($this->field, [['name' => 'Plumber', 'type' => 'primary']], occupationId: $this->occupation->id, locale: 'en');

    expect(fn () => recordValue($this->field, [['name' => 'x', 'type' => 'alias']], occupationId: $this->occupation->id, locale: 'en'))
        ->toThrow(InvalidFieldValue::class);

    expect(namesOf($this->occupation))->toBe([['en', 'Plumber', 'primary', null]])
        ->and(OccupationName::query()->where('type', NameType::Primary)->count())->toBe(1);
});

it('normalizes names into one search key per spelling', function (string $a, string $b) {
    $normalize = app(NormalizeName::class);

    expect($normalize->handle($a))->toBe($normalize->handle($b));
})->with([
    'case and spaces' => ['  Pipe   Fitter ', 'pipe fitter'],
    'punctuation' => ['pipe-fitter.', 'pipe fitter'],
    'danda' => ['নলমিস্ত্রি।', 'নলমিস্ত্রি'],
    'precomposed and decomposed য়' => ["\u{09AE}\u{09BE}\u{09DF}\u{09C7}\u{09B0}", "\u{09AE}\u{09BE}\u{09AF}\u{09BC}\u{09C7}\u{09B0}"],
    'zero-width joiners' => ["র\u{200D}্যাব", 'র্যাব'],
]);
