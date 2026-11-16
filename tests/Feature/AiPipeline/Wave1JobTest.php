<?php

use App\Modules\AiPipeline\Actions\GenerateWave1;
use App\Modules\AiPipeline\Enums\Wave1Status;
use App\Modules\AiPipeline\Exceptions\CrossCheckNeedsTwoModels;
use App\Modules\AiPipeline\Exceptions\ProviderError;
use App\Modules\AiPipeline\Jobs\GenerateWave1Job;
use App\Modules\AiPipeline\Models\AiRun;
use App\Modules\Knowledge\Enums\CrossCheck;
use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Knowledge\Models\FieldValue;
use App\Modules\Knowledge\Models\Revision;

/*
 * PLAN M12: Wave 1 runs the same prompt on two models. Model A's value is the draft, model B's is a
 * second opinion next to it, and the cross-check decides the review priority (D50).
 */

beforeEach(function () {
    $this->fake = wave1Setup();
    $this->occupation = listedOccupation();
});

function wave1Slot(int $occupationId, string $key): FieldValue
{
    return FieldValue::query()
        ->where('occupation_id', $occupationId)
        ->where('field_definition_id', FieldDefinition::query()->where('key', $key)->sole()->id)
        ->sole();
}

it('writes model A as the draft and model B as a second opinion, with the cross-check', function () {
    $this->fake->respondWithJson(wave1Output(['physical_workload' => 4]))
        ->respondWithJson(wave1Output(['physical_workload' => 1, 'mental_workload' => 2]));

    GenerateWave1Job::dispatchSync($this->occupation->id);

    [$a, $b] = AiRun::query()->where('prompt_key', 'wave1/fields')->orderBy('id')->get()->all();
    $workload = wave1Slot($this->occupation->id, 'physical_workload');
    $current = $workload->currentRevision;
    $second = Revision::query()->where('field_value_id', $workload->id)->where('ai_run_id', $b->id)->sole();

    expect([$a->model, $b->model])->toBe(['fake-model-a', 'fake-model-b'])
        ->and($current->ai_run_id)->toBe($a->id)
        ->and($current->new_value)->toBe(4)
        ->and($current->confidence)->toBe(0.6)
        ->and($workload->status)->toBe(FieldStatus::AiDraft)
        ->and($second->proposed)->toBeTrue()
        ->and($second->new_value)->toBe(1)
        ->and($workload->cross_check)->toBe(CrossCheck::Disagreed)
        ->and($workload->review_priority)->toBe(FieldValue::PRIORITY_MODELS_DISAGREE);

    $mental = wave1Slot($this->occupation->id, 'mental_workload');
    expect($mental->cross_check)->toBe(CrossCheck::Agreed)
        ->and($mental->review_priority)->toBe(FieldValue::PRIORITY_NONE)
        ->and(FieldValue::query()->where('occupation_id', $this->occupation->id)->whereNotNull('cross_check')->count())->toBe(11);
});

it('puts the context and the anchors in the prompt', function () {
    $this->fake->respondWithJson(wave1Output())->respondWithJson(wave1Output());

    app(GenerateWave1::class)->handle($this->occupation->id);

    $prompt = $this->fake->requests()[0]->prompt;
    expect($prompt)->toContain('প্লাম্বার; নলমিস্ত্রি (local)')
        ->toContain('Plumber')
        ->toContain('ISCO-08 unit group: 7126')
        ->toContain('Heavy lifting and carrying most of the day')
        ->toContain('- months_3_12: 3–12 months')
        ->and($this->fake->requests()[1]->prompt)->toBe($prompt);
});

it('keeps a field the model does not know as unknown', function () {
    $this->fake->respondWithJson(wave1Output(['seasonal' => 'unknown']))->respondWithJson(wave1Output(['seasonal' => 'unknown']));

    app(GenerateWave1::class)->handle($this->occupation->id);

    $seasonal = wave1Slot($this->occupation->id, 'seasonal');
    expect($seasonal->currentRevision->new_value)->toBeIn([null, 'unknown'])
        ->and($seasonal->cross_check)->toBe(CrossCheck::Agreed);
});

it('writes nothing when model B fails, and a rerun calls only model B again', function () {
    $this->fake->respondWithJson(wave1Output())->failWith(ProviderError::because('fake', 'bad request'));

    $result = app(GenerateWave1::class)->handle($this->occupation->id);

    expect($result->status)->toBe(Wave1Status::Failed)
        ->and(Revision::query()->whereIn('ai_run_id', AiRun::query()->where('prompt_key', 'wave1/fields')->select('id'))->count())->toBe(0);

    $calls = count($this->fake->requests());
    $this->fake->respondWithJson(wave1Output());
    $rerun = app(GenerateWave1::class)->handle($this->occupation->id);

    expect($rerun->status)->toBe(Wave1Status::Generated)
        ->and(count($this->fake->requests()) - $calls)->toBe(1);
});

it('writes nothing when an output does not fit the schema', function () {
    $this->fake->respondWithJson(wave1Output())->respondWithJson(['fields' => ['physical_workload' => ['value' => 9]]]);

    $result = app(GenerateWave1::class)->handle($this->occupation->id);

    expect($result->status)->toBe(Wave1Status::Failed)
        ->and(FieldValue::query()->where('occupation_id', $this->occupation->id)->whereNotNull('cross_check')->count())->toBe(0);
});

it('skips an occupation whose runs are already written', function () {
    $this->fake->respondWithJson(wave1Output())->respondWithJson(wave1Output());
    app(GenerateWave1::class)->handle($this->occupation->id);
    $calls = count($this->fake->requests());
    $revisions = Revision::query()->count();

    $again = app(GenerateWave1::class)->handle($this->occupation->id);

    expect($again->status)->toBe(Wave1Status::Skipped)
        ->and(count($this->fake->requests()))->toBe($calls)
        ->and(Revision::query()->count())->toBe($revisions);
});

it('refuses a cross-check with the same model twice', function () {
    config(['atlas.ai.tasks.wave1.b' => config('atlas.ai.tasks.wave1.a')]);

    expect(fn () => app(GenerateWave1::class)->handle($this->occupation->id))->toThrow(CrossCheckNeedsTwoModels::class)
        ->and(AiRun::query()->where('prompt_key', 'wave1/fields')->count())->toBe(0);
});
