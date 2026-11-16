<?php

use App\Modules\AiPipeline\Enums\AiRunStatus;
use App\Modules\AiPipeline\Models\AiRun;
use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Knowledge\Models\FieldValue;
use App\Modules\Knowledge\Models\Revision;
use App\Modules\Taxonomy\Actions\ExpandUnitGroup;
use App\Modules\Taxonomy\Enums\ExpansionStatus;
use App\Modules\Taxonomy\Models\IscoGroup;
use App\Modules\Taxonomy\Models\Occupation;
use App\Modules\Taxonomy\Models\OccupationName;

/*
 * PLAN M10: an expansion is written whole or not at all. Output that passes the schema but breaks a
 * Knowledge rule rolls back every occupation of that run, also the ones before the bad one.
 */

beforeEach(function () {
    $this->fake = fakeExpansionAi();
    FieldDefinition::factory()->names()->create();
    IscoGroup::factory()->create(['code' => '7126']);
});

dataset('broken outputs', [
    'not JSON' => fn () => fn ($fake) => $fake->respond('Here are some occupations: plumber, pipe fitter'),
    'missing fields' => fn () => fn ($fake) => $fake->respondWithJson(['occupations' => [['names_en' => []]], 'note' => '']),
    'two primary names' => fn () => fn ($fake) => $fake->respondWithJson(expansionOutput(namesEn: [
        ['name' => 'Plumber', 'type' => 'primary'],
        ['name' => 'Pipe fitter', 'type' => 'primary'],
    ])),
]);

it('writes nothing for output that fails the schema', function (Closure $respond) {
    $respond($this->fake);

    $result = app(ExpandUnitGroup::class)->handle('7126');

    expect($result->status)->toBe(ExpansionStatus::Failed)
        ->and(AiRun::query()->sole()->status)->toBe(AiRunStatus::Failed)
        ->and(Occupation::query()->count())->toBe(0);
})->with('broken outputs');

it('rolls back the whole run when one occupation breaks a Knowledge rule', function () {
    $good = expansionOutput()['occupations'][0];
    $bad = expansionOutput(namesEn: [
        ['name' => 'Pipe fitter', 'type' => 'primary'],
        ['name' => 'pipe  FITTER', 'type' => 'alias'],
    ])['occupations'][0];
    $this->fake->respondWithJson(['occupations' => [$good, $bad], 'note' => '']);

    $result = app(ExpandUnitGroup::class)->handle('7126');

    expect($result->status)->toBe(ExpansionStatus::Rejected)
        ->and($result->error)->toContain('listed twice')
        ->and($result->occupationIds)->toBe([])
        ->and(AiRun::query()->sole()->status)->toBe(AiRunStatus::Succeeded)
        ->and(Occupation::query()->count())->toBe(0)
        ->and(FieldValue::query()->count())->toBe(0)
        ->and(Revision::query()->count())->toBe(0)
        ->and(OccupationName::query()->count())->toBe(0);
});
