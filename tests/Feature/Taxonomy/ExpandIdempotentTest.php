<?php

use App\Modules\AiPipeline\AiGateway;
use App\Modules\AiPipeline\Models\AiRun;
use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Taxonomy\Actions\BuildExpansionRequest;
use App\Modules\Taxonomy\Actions\ExpandUnitGroup;
use App\Modules\Taxonomy\Enums\ExpansionStatus;
use App\Modules\Taxonomy\Models\IscoGroup;
use App\Modules\Taxonomy\Models\Occupation;

/*
 * PLAN M10 / D45: running the same expansion again makes no second call and no second set of
 * occupations. --force makes a new call.
 */

beforeEach(function () {
    $this->fake = fakeExpansionAi();
    FieldDefinition::factory()->names()->create();
    IscoGroup::factory()->create(['code' => '7126']);
});

it('skips a unit group whose earlier run already wrote its occupations', function () {
    $this->fake->respondWithJson(expansionOutput());
    $first = app(ExpandUnitGroup::class)->handle('7126');

    $second = app(ExpandUnitGroup::class)->handle('7126');

    expect($first->status)->toBe(ExpansionStatus::Expanded)
        ->and($second->status)->toBe(ExpansionStatus::Skipped)
        ->and($second->runId)->toBe($first->runId)
        ->and($second->occupationIds)->toBe($first->occupationIds)
        ->and(AiRun::query()->count())->toBe(1)
        ->and(Occupation::query()->count())->toBe(1)
        ->and($this->fake->requests())->toHaveCount(1);
});

it('skips a unit group whose earlier run proposed nothing', function () {
    $this->fake->respondWithJson(['occupations' => [], 'note' => 'Covered by other groups.']);
    app(ExpandUnitGroup::class)->handle('7126');

    expect(app(ExpandUnitGroup::class)->handle('7126')->status)->toBe(ExpansionStatus::Skipped)
        ->and(AiRun::query()->count())->toBe(1);
});

it('writes an earlier succeeded output that was never written, without a new call', function () {
    // A run that succeeded, but its job stopped before the output was written.
    $this->fake->respondWithJson(expansionOutput());
    app(AiGateway::class)->complete(app(BuildExpansionRequest::class)->handle('7126'));

    $again = app(ExpandUnitGroup::class)->handle('7126');

    expect($again->status)->toBe(ExpansionStatus::Expanded)
        ->and(AiRun::query()->count())->toBe(1)
        ->and(Occupation::query()->sole()->ai_run_id)->toBe($again->runId);
});

it('makes a new call with force', function () {
    $this->fake->respondWithJson(expansionOutput())->respondWithJson(expansionOutput());
    app(ExpandUnitGroup::class)->handle('7126');

    $forced = app(ExpandUnitGroup::class)->handle('7126', force: true);

    expect($forced->status)->toBe(ExpansionStatus::Expanded)
        ->and(AiRun::query()->count())->toBe(2)
        ->and(Occupation::query()->pluck('slug')->sort()->values()->all())->toBe(['plumber', 'plumber-2']);
});
