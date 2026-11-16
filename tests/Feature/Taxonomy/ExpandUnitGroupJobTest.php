<?php

use App\Modules\AiPipeline\Enums\AiRunStatus;
use App\Modules\AiPipeline\Models\AiRun;
use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Knowledge\Models\FieldValue;
use App\Modules\Taxonomy\Actions\BuildExpansionRequest;
use App\Modules\Taxonomy\Actions\ExpandUnitGroup;
use App\Modules\Taxonomy\Enums\ExpansionStatus;
use App\Modules\Taxonomy\Enums\PublishState;
use App\Modules\Taxonomy\Jobs\ExpandUnitGroupJob;
use App\Modules\Taxonomy\Models\IscoGroup;
use App\Modules\Taxonomy\Models\Occupation;
use App\Modules\Taxonomy\Models\OccupationName;

/*
 * PLAN M10: one unit group expanded through the fake provider becomes candidate occupations, each
 * linked to its run, with its names as ai_draft Knowledge values and the search copy filled.
 */

beforeEach(function () {
    $this->fake = fakeExpansionAi();
    $this->names = FieldDefinition::factory()->names()->create();
    IscoGroup::factory()->create(['code' => '7126', 'title_en' => 'Plumbers and pipe fitters', 'title_bn' => 'প্লাম্বার ও পাইপ ফিটার']);
});

it('writes each proposed occupation as a candidate with ai_draft names', function () {
    $this->fake->respondWithJson(expansionOutput());

    ExpandUnitGroupJob::dispatchSync('7126');

    $run = AiRun::query()->sole();
    $occupation = Occupation::query()->sole();

    expect($run->status)->toBe(AiRunStatus::Succeeded)
        ->and($run->prompt_key)->toBe('taxonomy/expand-unit-group')
        ->and($occupation->publish_state)->toBe(PublishState::Candidate)
        ->and($occupation->isco_code)->toBe('7126')
        ->and($occupation->slug)->toBe('plumber')
        ->and($occupation->ai_run_id)->toBe($run->id);

    $values = FieldValue::query()->where('occupation_id', $occupation->id)->orderBy('locale')->get();
    expect($values)->toHaveCount(2)
        ->and($values->pluck('status')->all())->toBe([FieldStatus::AiDraft, FieldStatus::AiDraft])
        ->and($values->pluck('locale')->all())->toBe(['bn', 'en']);

    $names = OccupationName::query()->where('occupation_id', $occupation->id)->orderBy('locale')->orderBy('id')
        ->get()->map(fn (OccupationName $name) => [$name->locale, $name->name, $name->type->value, $name->region])->all();

    // The specialization becomes an alias in each language.
    expect($names)->toBe([
        ['bn', 'প্লাম্বার', 'primary', null],
        ['bn', 'নলমিস্ত্রি', 'local', null],
        ['bn', 'স্যানিটারি মিস্ত্রি', 'alias', null],
        ['en', 'Plumber', 'primary', null],
        ['en', 'Sanitary fitter', 'alias', null],
    ]);
});

it('sends the unit group, its place in ISCO-08 and the accepted occupations as context', function () {
    $listed = Occupation::factory()->create(['isco_code' => '7126', 'publish_state' => PublishState::Listed]);
    recordValue($this->names, [['name' => 'Pipe fitter', 'type' => 'primary']], occupationId: $listed->id, locale: 'en');
    Occupation::factory()->create(['isco_code' => '7126', 'publish_state' => PublishState::Candidate]);
    $this->fake->respondWithJson(['occupations' => [], 'note' => '']);

    ExpandUnitGroupJob::dispatchSync('7126');

    $values = app(BuildExpansionRequest::class)->handle('7126')->values;
    $sent = $this->fake->requests()[0]->prompt;

    expect($values['hierarchy'])->toBe('7 Test group 7 > 71 Test group 71 > 712 Test group 712 > 7126 Plumbers and pipe fitters')
        ->and($values['group_title_bn'])->toBe('প্লাম্বার ও পাইপ ফিটার')
        ->and($values['existing_occupations'])->toBe('- Pipe fitter / unknown')
        ->and($sent)->toContain('7126')->toContain('Pipe fitter');
});

it('records a failed run and writes nothing when the output breaks the schema', function () {
    $this->fake->respondWithJson(['occupations' => [['names_en' => [['name' => 'Plumber', 'type' => 'primary']]]], 'note' => '']);

    $result = app(ExpandUnitGroup::class)->handle('7126');

    expect($result->status)->toBe(ExpansionStatus::Failed)
        ->and(AiRun::query()->sole()->status)->toBe(AiRunStatus::Failed)
        ->and(Occupation::query()->count())->toBe(0);
});
