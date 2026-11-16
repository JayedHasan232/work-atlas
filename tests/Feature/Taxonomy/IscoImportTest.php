<?php

use App\Modules\Knowledge\Models\Source;
use App\Modules\Taxonomy\Actions\ImportIscoGroups;
use App\Modules\Taxonomy\Exceptions\InvalidTaxonomyFile;
use App\Modules\Taxonomy\Models\IscoGroup;

test('the import creates every group with its level and parent', function () {
    $result = app(ImportIscoGroups::class)->handle(taxonomyFixture(), fixtureSource());

    expect($result->created)->toBe(11)
        ->and($result->updated)->toBe(0)
        ->and($result->unchanged)->toBe(0)
        ->and($result->groupsPerLevel)->toBe([1 => 2, 2 => 2, 3 => 3, 4 => 4]);

    $groups = iscoGroupsByCode();

    expect($groups['1']->level)->toBe(1)
        ->and($groups['1']->parent_code)->toBeNull()
        ->and($groups['1120']->level)->toBe(4)
        ->and($groups['1120']->parent_code)->toBe('112')
        ->and($groups['1111']->title_en)->toBe('Test unit group 1111')
        ->and($groups['1111']->title_bn)->toBeNull()
        ->and($groups['1111']->bsco_code)->toBeNull();
});

test('every unit group has its full parent chain up to a major group', function () {
    app(ImportIscoGroups::class)->handle(taxonomyFixture(), fixtureSource());
    $groups = iscoGroupsByCode();
    $unitGroups = array_filter($groups, fn (IscoGroup $group): bool => $group->level === IscoGroup::UNIT_GROUP_LEVEL);

    expect($unitGroups)->toHaveCount(4);

    foreach ($unitGroups as $group) {
        $chain = [];

        for ($current = $group; $current->parent_code !== null; $current = $groups[$current->parent_code]) {
            $chain[] = $current->parent_code;
        }

        expect($chain)->toBe([substr($group->code, 0, 3), substr($group->code, 0, 2), substr($group->code, 0, 1)]);
    }
});

test('a byte order mark, Windows line ends, quoted titles and blank lines are read', function () {
    $file = temporaryFile("\u{FEFF}code,title_en\r\n1,\"Test major group, with a comma\"\r\n\r\n11,Test sub-major group 11\r\n");

    $result = app(ImportIscoGroups::class)->handle($file, fixtureSource());

    expect($result->created)->toBe(2)
        ->and(iscoGroupsByCode()['1']->title_en)->toBe('Test major group, with a comma');
});

test('a file that cannot be imported as a whole writes nothing', function (string $contents, string $reason) {
    $file = temporaryFile($contents);

    expect(fn () => app(ImportIscoGroups::class)->handle($file, fixtureSource()))
        ->toThrow(InvalidTaxonomyFile::class, $reason)
        ->and(IscoGroup::query()->count())->toBe(0)
        ->and(Source::query()->count())->toBe(0);
})->with([
    'wrong header' => ["code,title\n1,Test major group 1\n", 'the header must be'],
    'extra column' => ["code,title_en\n1,Test major group 1,extra\n", 'line 2 must have 2 columns'],
    'missing column' => ["code,title_en\n1\n", 'line 2 must have 2 columns'],
    'letters in a code' => ["code,title_en\n1a,Test\n", '"1a" is not an ISCO code'],
    'five digits' => ["code,title_en\n1,A\n11,B\n111,C\n1111,D\n11111,E\n", '"11111" is not an ISCO code'],
    'empty title' => ["code,title_en\n1,\n", 'group 1 has no English title'],
    'code listed twice' => ["code,title_en\n1,A\n1,B\n", 'group 1 is listed twice'],
    'missing parent' => ["code,title_en\n1,A\n11,B\n1111,D\n", 'group 1111 has no parent group 111'],
    'header only' => ["code,title_en\n", 'the file has no groups'],
    'empty file' => ['', 'the file is empty'],
]);

test('a file that does not exist writes nothing', function () {
    expect(fn () => app(ImportIscoGroups::class)->handle(taxonomyFixture('missing.csv'), fixtureSource()))
        ->toThrow(InvalidTaxonomyFile::class, 'the file does not exist')
        ->and(Source::query()->count())->toBe(0);
});
