<?php

use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Taxonomy\Actions\DetectDuplicates;
use App\Modules\Taxonomy\Enums\DuplicateStatus;
use App\Modules\Taxonomy\Enums\PublishState;
use App\Modules\Taxonomy\Models\DuplicateCandidate;
use App\Modules\Taxonomy\Models\IscoGroup;

/*
 * PLAN M11, A8: possible duplicates are found by trigram similarity and shared names, inside one ISCO-08
 * minor group, and stored once per pair.
 */

beforeEach(function () {
    FieldDefinition::factory()->names()->create();
    IscoGroup::factory()->create(['code' => '7126']);
    IscoGroup::factory()->create(['code' => '7127']);
    IscoGroup::factory()->create(['code' => '7411']);
});

it('flags two occupations that share a name after normalization, with similarity 1', function () {
    $a = occupationNamed(['bn' => [['name' => 'প্লাম্বার', 'type' => 'primary'], ['name' => 'নলমিস্ত্রি', 'type' => 'local']]]);
    $b = occupationNamed(['bn' => [['name' => 'পানির মিস্ত্রি', 'type' => 'primary'], ['name' => "নলমিস্ত্রি\u{200C}", 'type' => 'alias']]], ['isco_code' => '7127']);

    expect(app(DetectDuplicates::class)->handle())->toBe(1);

    $pair = DuplicateCandidate::query()->sole();
    expect($pair->occupation_id)->toBe($a->id)
        ->and($pair->other_occupation_id)->toBe($b->id)
        ->and($pair->similarity)->toBe('1.000')
        ->and([$pair->locale, $pair->name, $pair->other_name])->toBe(['bn', 'নলমিস্ত্রি', "নলমিস্ত্রি\u{200C}"])
        ->and($pair->status)->toBe(DuplicateStatus::Open);
});

it('flags similar names by trigram, in English and in Bangla', function () {
    occupationNamed(['en' => [['name' => 'Plumber', 'type' => 'primary']]]);
    occupationNamed(['en' => [['name' => 'Plumbers', 'type' => 'primary']]]);
    occupationNamed(['bn' => [['name' => 'বিদ্যুৎ মিস্ত্রি', 'type' => 'primary']]]);
    occupationNamed(['bn' => [['name' => 'বিদ্যুত মিস্ত্রী', 'type' => 'primary']]]);

    expect(app(DetectDuplicates::class)->handle())->toBe(2)
        ->and(DuplicateCandidate::query()->pluck('locale')->sort()->values()->all())->toBe(['bn', 'en'])
        ->and(DuplicateCandidate::query()->get()->every(fn ($pair) => (float) $pair->similarity >= 0.5 && (float) $pair->similarity < 1))->toBeTrue();
});

it('leaves out different names, other minor groups, other languages, merged and rejected occupations', function () {
    occupationNamed(['en' => [['name' => 'Plumber', 'type' => 'primary']]]);
    occupationNamed(['en' => [['name' => 'Tailor', 'type' => 'primary']]]);
    occupationNamed(['en' => [['name' => 'Plumber', 'type' => 'primary']]], ['isco_code' => '7411']);
    occupationNamed(['bn' => [['name' => 'Plumber', 'type' => 'primary']]]);
    occupationNamed(['en' => [['name' => 'Plumber', 'type' => 'primary']]], ['publish_state' => PublishState::Rejected]);

    expect(app(DetectDuplicates::class)->handle())->toBe(0);
});

it('stores a pair once, however often it runs', function () {
    occupationNamed(['en' => [['name' => 'Plumber', 'type' => 'primary'], ['name' => 'Pipe fitter', 'type' => 'alias']]]);
    occupationNamed(['en' => [['name' => 'Plumber', 'type' => 'primary'], ['name' => 'Pipe fitter', 'type' => 'alias']]]);

    expect(app(DetectDuplicates::class)->handle())->toBe(1)
        ->and(app(DetectDuplicates::class)->handle())->toBe(0)
        ->and(DuplicateCandidate::query()->count())->toBe(1);
});

it('runs from the command with a threshold', function () {
    occupationNamed(['en' => [['name' => 'Plumber', 'type' => 'primary']]]);
    occupationNamed(['en' => [['name' => 'Plumbers', 'type' => 'primary']]]);

    $this->artisan('atlas:taxonomy:duplicates', ['--threshold' => '0.95'])->expectsOutputToContain('0 new possible duplicate pairs')->assertSuccessful();
    $this->artisan('atlas:taxonomy:duplicates')->expectsOutputToContain('1 new possible duplicate pairs')->assertSuccessful();
    $this->artisan('atlas:taxonomy:duplicates', ['--threshold' => '2'])->assertFailed();
});
