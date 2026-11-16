<?php

use App\Modules\Knowledge\Actions\ValidateFieldValue;
use App\Modules\Knowledge\Exceptions\InvalidFieldValue;
use App\Modules\Knowledge\Models\FieldDefinition;

/*
 * The `names` field (D44): an occupation's names in one language.
 */

function validateNames(mixed $value, ?string $locale = 'bn', ?string $region = null): mixed
{
    return app(ValidateFieldValue::class)->handle(FieldDefinition::factory()->names()->make(), $value, $locale, $region);
}

it('stores a name list primary first, trimmed, with a region only on regional names', function () {
    expect(validateNames([
        ['name' => 'নলমিস্ত্রি', 'type' => 'alias'],
        ['name' => '  প্লাম্বার  ', 'type' => 'primary'],
        ['name' => 'পানির মিস্ত্রি', 'type' => 'regional', 'region' => 'sylhet'],
    ]))->toBe([
        ['name' => 'প্লাম্বার', 'type' => 'primary'],
        ['name' => 'নলমিস্ত্রি', 'type' => 'alias'],
        ['name' => 'পানির মিস্ত্রি', 'type' => 'regional', 'region' => 'sylhet'],
    ]);
});

it('treats null and unknown as no names', function () {
    expect(validateNames(null))->toBeNull()->and(validateNames('unknown'))->toBeNull();
});

it('rejects a name list that breaks a rule', function (mixed $value, string $reason) {
    expect(fn () => validateNames($value))->toThrow(InvalidFieldValue::class, $reason);
})->with([
    'empty' => [[], 'a list of 1 to'],
    'not a list' => [['name' => 'x', 'type' => 'primary'], 'a list of 1 to'],
    'too many' => [array_map(fn (int $i) => ['name' => "name {$i}", 'type' => $i === 0 ? 'primary' : 'alias'], range(0, 50)), 'a list of 1 to'],
    'no primary' => [[['name' => 'নলমিস্ত্রি', 'type' => 'alias']], 'exactly one primary'],
    'two primaries' => [[['name' => 'a', 'type' => 'primary'], ['name' => 'b', 'type' => 'primary']], 'exactly one primary'],
    'an unknown type' => [[['name' => 'a', 'type' => 'nickname']], 'a name type is one of'],
    'an empty name' => [[['name' => '   ', 'type' => 'primary']], 'a name needs'],
    'a name too long' => [[['name' => str_repeat('ক', 201), 'type' => 'primary']], 'a name needs'],
    'an unknown key' => [[['name' => 'a', 'type' => 'primary', 'gender' => 'f']], 'each name is'],
    'a regional name without a region' => [[['name' => 'a', 'type' => 'primary'], ['name' => 'b', 'type' => 'regional']], 'a regional name needs a region'],
    'a region on an alias' => [[['name' => 'a', 'type' => 'primary'], ['name' => 'b', 'type' => 'alias', 'region' => 'sylhet']], 'only a regional name has one'],
    'a bad region key' => [[['name' => 'a', 'type' => 'primary'], ['name' => 'b', 'type' => 'regional', 'region' => 'Sylhet Division']], 'a region is a short lower-case key'],
    'a name twice, in another case' => [[['name' => 'Plumber', 'type' => 'primary'], ['name' => 'plumber', 'type' => 'alias']], 'listed twice'],
]);

it('needs a locale and refuses a slot region', function (?string $locale, ?string $region, string $reason) {
    expect(fn () => validateNames([['name' => 'a', 'type' => 'primary']], $locale, $region))->toThrow(InvalidFieldValue::class, $reason);
})->with([
    'no locale' => [null, null, 'needs a supported locale'],
    'an unsupported locale' => ['fr', null, 'needs a supported locale'],
    'a slot region' => ['bn', 'sylhet', 'a name list is for all of Bangladesh'],
]);

it('generates a JSON schema that accepts exactly one primary name', function () {
    $schema = json_decode((string) file_get_contents(base_path('data/schemas/wave0/fields.v1.json')), true);
    $names = $schema['properties']['fields']['properties']['names']['properties']['value']['anyOf'][0] ?? null;

    expect($names)->not->toBeNull()
        ->and($names['minContains'])->toBe(1)
        ->and($names['maxContains'])->toBe(1);
});
