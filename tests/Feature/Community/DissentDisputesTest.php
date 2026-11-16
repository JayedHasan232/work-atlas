<?php

use App\Models\User;
use App\Modules\Community\Enums\TaskAnswer;
use App\Modules\Community\Queries\NextVerificationTask;
use App\Modules\Knowledge\Actions\ChangeStatus;
use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Events\FieldStatusChanged;
use App\Modules\Knowledge\Models\FieldValue;
use Illuminate\Support\Facades\Event;

/*
 * PLAN M17: one "no" makes the value `disputed` and puts it in the reviewers' queue, above an
 * ordinary proposal and below models that disagreed. A reviewer's decision takes it out again.
 */

beforeEach(function () {
    wave1Setup();
});

it('disputes a value on one disagreeing answer and queues it for a reviewer', function () {
    $task = taskFor();

    answerTask($task, TaskAnswer::Disagree);

    expect(FieldValue::query()->findOrFail($task->field_value_id))
        ->status->toBe(FieldStatus::Disputed)
        ->review_priority->toBe(FieldValue::PRIORITY_COMMUNITY_DISSENT);
});

it('keeps a higher review priority', function () {
    $task = taskFor();
    FieldValue::query()->whereKey($task->field_value_id)->update(['review_priority' => FieldValue::PRIORITY_MODELS_DISAGREE]);

    answerTask($task, TaskAnswer::Disagree);

    expect(FieldValue::query()->findOrFail($task->field_value_id)->review_priority)->toBe(FieldValue::PRIORITY_MODELS_DISAGREE);
});

it('announces the status change', function () {
    $task = taskFor();
    Event::fake([FieldStatusChanged::class]);

    answerTask($task, TaskAnswer::Disagree);

    Event::assertDispatched(FieldStatusChanged::class);
});

it('leaves the queue when a reviewer decides', function () {
    $task = taskFor();
    answerTask($task, TaskAnswer::Disagree);

    app(ChangeStatus::class)->handle($task->field_value_id, FieldStatus::Verified, reviewerActor());

    expect(FieldValue::query()->findOrFail($task->field_value_id))
        ->status->toBe(FieldStatus::Verified)
        ->review_priority->toBe(FieldValue::PRIORITY_NONE);
});

it('does not ask about a disputed value again', function () {
    $task = taskFor();
    answerTask($task, TaskAnswer::Disagree);

    expect(app(NextVerificationTask::class)->handle(User::factory()->create()->id))->toBeNull();
});
