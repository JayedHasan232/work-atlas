<?php

use App\Modules\Community\Actions\GenerateVerificationTasks;
use App\Modules\Community\Enums\TaskState;
use App\Modules\Community\Models\VerificationTask;
use App\Modules\Knowledge\Actions\RecordSource;
use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Knowledge\Models\FieldValue;
use App\Modules\Taxonomy\Enums\PublishState;

/*
 * PLAN M17, D64: one open question per checkable value — Wave 1, not sensitive, `ai_draft` or
 * `sourced`, on a public occupation. Safe to run again; a changed value replaces its question.
 */

beforeEach(function () {
    wave1Setup();
    $this->field = fn (string $key): FieldDefinition => FieldDefinition::query()->where('key', $key)->sole();
    $this->generate = fn (): array => app(GenerateVerificationTasks::class)->handle();
});

it('makes one question per checkable value and is safe to run again', function () {
    $occupation = listedOccupation();
    recordValue(($this->field)('physical_workload'), 3, occupationId: $occupation->id);
    recordValue(($this->field)('mental_workload'), 2, occupationId: $occupation->id);

    expect(($this->generate)())->toBe(['created' => 2, 'stale' => 0])
        ->and(($this->generate)())->toBe(['created' => 0, 'stale' => 0])
        ->and(VerificationTask::query()->pluck('state')->all())->toBe([TaskState::Open, TaskState::Open])
        ->and(VerificationTask::query()->value('occupation_id'))->toBe($occupation->id);
});

it('asks about sourced values too', function () {
    $source = app(RecordSource::class)->handle(fixtureSource());
    $revision = recordValue(($this->field)('physical_workload'), 3, sourceIds: [$source], occupationId: listedOccupation()->id);

    ($this->generate)();

    expect(VerificationTask::query()->sole()->revision_id)->toBe($revision->id);
});

it('leaves out names, settled values and other waves', function () {
    $occupation = listedOccupation();
    $settled = recordValue(($this->field)('physical_workload'), 3, occupationId: $occupation->id);
    FieldValue::query()->whereKey($settled->field_value_id)->update(['status' => FieldStatus::Verified]);
    $later = FieldDefinition::factory()->text()->create(['wave' => 2]);
    recordValue($later, 'A description.', occupationId: $occupation->id, locale: 'en');

    expect(($this->generate)()['created'])->toBe(0);
});

it('never asks about a sensitive value', function () {
    $sensitive = FieldDefinition::factory()->text()->sensitive()->create(['wave' => 1]);
    $source = app(RecordSource::class)->handle(fixtureSource());
    recordValue($sensitive, 'A legal requirement.', sourceIds: [$source], occupationId: listedOccupation()->id, locale: 'en');

    expect(($this->generate)()['created'])->toBe(0);
});

it('asks only about occupations with a public page', function () {
    $hidden = listedOccupation();
    $hidden->update(['publish_state' => PublishState::Candidate]);
    recordValue(($this->field)('physical_workload'), 3, occupationId: $hidden->id);

    expect(($this->generate)()['created'])->toBe(0);
});

it('replaces the question when the value changes', function () {
    $occupation = listedOccupation();
    recordValue(($this->field)('physical_workload'), 3, occupationId: $occupation->id);
    ($this->generate)();
    $newer = recordValue(($this->field)('physical_workload'), 4, occupationId: $occupation->id);

    expect(($this->generate)())->toBe(['created' => 1, 'stale' => 1])
        ->and(VerificationTask::query()->where('state', TaskState::Open)->sole()->revision_id)->toBe($newer->id);
});

it('closes the questions of an occupation that is no longer public', function () {
    $occupation = listedOccupation();
    recordValue(($this->field)('physical_workload'), 3, occupationId: $occupation->id);
    ($this->generate)();
    $occupation->update(['publish_state' => PublishState::Rejected]);

    expect(($this->generate)())->toBe(['created' => 0, 'stale' => 1]);
});

it('runs from the command line', function () {
    recordValue(($this->field)('physical_workload'), 3, occupationId: listedOccupation()->id);

    $this->artisan('atlas:tasks:generate')->assertSuccessful();

    expect(VerificationTask::query()->count())->toBe(1);
});
