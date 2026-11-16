<?php

use App\Modules\Knowledge\Models\Source;
use App\Modules\Taxonomy\Actions\ImportIscoGroups;
use App\Modules\Taxonomy\Models\IscoGroup;

test('a second run of the same file changes nothing', function () {
    $import = app(ImportIscoGroups::class);
    $import->handle(taxonomyFixture(), fixtureSource());
    $before = IscoGroup::query()->orderBy('code')->get()->toArray();

    $this->travel(1)->days();
    $result = $import->handle(taxonomyFixture(), fixtureSource());

    expect($result->changedAnything())->toBeFalse()
        ->and($result->unchanged)->toBe(11)
        ->and(IscoGroup::query()->orderBy('code')->get()->toArray())->toBe($before)
        ->and(Source::query()->count())->toBe(1);
});

test('a changed title updates only that group', function () {
    $import = app(ImportIscoGroups::class);
    $import->handle(taxonomyFixture(), fixtureSource());
    $before = iscoGroupsByCode();

    $this->travel(1)->days();
    $changed = str_replace('Test unit group 1112', 'Renamed unit group 1112', (string) file_get_contents(taxonomyFixture()));
    $result = $import->handle(temporaryFile($changed), fixtureSource());
    $after = iscoGroupsByCode();

    expect($result->created)->toBe(0)
        ->and($result->updated)->toBe(1)
        ->and($result->unchanged)->toBe(10)
        ->and($after['1112']->title_en)->toBe('Renamed unit group 1112')
        ->and($after['1112']->updated_at?->isAfter($before['1112']->updated_at))->toBeTrue()
        ->and($after['1111']->updated_at?->equalTo($before['1111']->updated_at))->toBeTrue();
});

test('the import keeps Bangla titles, BSCO codes and groups the file no longer lists', function () {
    $import = app(ImportIscoGroups::class);
    $import->handle(taxonomyFixture(), fixtureSource());
    IscoGroup::query()->where('code', '1111')->update(['title_bn' => 'পরীক্ষার একক গ্রুপ', 'bsco_code' => '1111']);

    $changed = str_replace('Test unit group 1111', 'Renamed unit group 1111', (string) file_get_contents(taxonomyFixture()));
    $withoutLastGroup = preg_replace('/^2111,.*\n/m', '', $changed);
    $result = $import->handle(temporaryFile((string) $withoutLastGroup), fixtureSource());
    $groups = iscoGroupsByCode();

    expect($result->updated)->toBe(1)
        ->and($groups['1111']->title_en)->toBe('Renamed unit group 1111')
        ->and($groups['1111']->title_bn)->toBe('পরীক্ষার একক গ্রুপ')
        ->and($groups['1111']->bsco_code)->toBe('1111')
        ->and($groups)->toHaveKey('2111');
});
