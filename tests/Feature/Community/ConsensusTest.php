<?php

use App\Models\User;
use App\Modules\Community\Enums\TaskAnswer;
use App\Modules\Community\Enums\TaskState;
use App\Modules\Community\Models\VerificationTask;
use App\Modules\Knowledge\Actions\ApplyConsensus;
use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Knowledge\Models\FieldValue;

/*
 * PLAN M17, A11b: three people agree and nobody disagrees → `community_supported`; one "no" →
 * `disputed`. "Not sure" counts for nothing. The people who agreed with a reached consensus get a
 * trust counter.
 */

beforeEach(function () {
    wave1Setup();
});

it('settles a value from the answers', function (array $answers, TaskState $state, FieldStatus $status) {
    $task = taskFor();

    foreach ($answers as $answer) {
        answerTask($task, $answer);
    }

    expect($task->refresh()->state)->toBe($state)
        ->and(FieldValue::query()->findOrFail($task->field_value_id)->status)->toBe($status);
})->with([
    'three agree' => [[TaskAnswer::Agree, TaskAnswer::Agree, TaskAnswer::Agree], TaskState::Supported, FieldStatus::CommunitySupported],
    'two agree' => [[TaskAnswer::Agree, TaskAnswer::Agree], TaskState::Open, FieldStatus::AiDraft],
    'two agree, two not sure' => [[TaskAnswer::Agree, TaskAnswer::Skip, TaskAnswer::Agree, TaskAnswer::Skip], TaskState::Open, FieldStatus::AiDraft],
    'only not sure' => [[TaskAnswer::Skip, TaskAnswer::Skip, TaskAnswer::Skip, TaskAnswer::Skip], TaskState::Open, FieldStatus::AiDraft],
    'one disagrees first' => [[TaskAnswer::Disagree], TaskState::Disputed, FieldStatus::Disputed],
    'two agree, one disagrees' => [[TaskAnswer::Agree, TaskAnswer::Agree, TaskAnswer::Disagree], TaskState::Disputed, FieldStatus::Disputed],
]);

it('closes a settled task to further answers', function () {
    $task = taskFor();
    answerTask($task, TaskAnswer::Agree);
    answerTask($task, TaskAnswer::Agree);
    answerTask($task, TaskAnswer::Agree);

    expect(fn () => answerTask($task, TaskAnswer::Disagree))->toThrow(fn (Exception $e) => expect($e->reason)->toBe('state'))
        ->and(FieldValue::query()->findOrFail($task->field_value_id)->status)->toBe(FieldStatus::CommunitySupported);
});

it('uses the configured number of agreements', function () {
    config(['atlas.community.consensus_agreements' => 2]);
    $task = taskFor();

    answerTask($task, TaskAnswer::Agree);
    answerTask($task, TaskAnswer::Agree);

    expect($task->refresh()->state)->toBe(TaskState::Supported);
});

it('credits the people who agreed with a reached consensus', function () {
    $task = taskFor();
    $people = [answerTask($task, TaskAnswer::Agree), answerTask($task, TaskAnswer::Agree)];
    $unsure = answerTask($task, TaskAnswer::Skip);
    $people[] = answerTask($task, TaskAnswer::Agree);

    expect(array_map(fn (User $user): int => $user->refresh()->confirmed_answers_count, $people))->toBe([1, 1, 1])
        ->and($unsure->refresh()->confirmed_answers_count)->toBe(0);
});

it('credits nobody when the value is disputed', function () {
    $task = taskFor();
    $agreed = answerTask($task, TaskAnswer::Agree);
    answerTask($task, TaskAnswer::Disagree);

    expect($agreed->refresh()->confirmed_answers_count)->toBe(0);
});

it('goes stale when the value changed before consensus', function () {
    $task = taskFor();
    answerTask($task, TaskAnswer::Agree);
    answerTask($task, TaskAnswer::Agree);
    $newer = recordValue(FieldDefinition::query()->where('key', 'physical_workload')->sole(), 4, occupationId: $task->occupation_id);

    $last = answerTask($task, TaskAnswer::Agree);

    $slot = FieldValue::query()->findOrFail($task->field_value_id);
    expect($task->refresh()->state)->toBe(TaskState::Stale)
        ->and($slot->current_revision_id)->toBe($newer->id)
        ->and($slot->status)->toBe(FieldStatus::AiDraft)
        ->and($last->refresh()->confirmed_answers_count)->toBe(0);
});

it('never changes a verified, sensitive or other value through consensus', function () {
    $task = taskFor();
    FieldValue::query()->whereKey($task->field_value_id)->update(['status' => FieldStatus::Verified]);
    $apply = app(ApplyConsensus::class);

    expect($apply->handle($task->field_value_id, $task->revision_id, FieldStatus::CommunitySupported))->toBeFalse()
        ->and(FieldValue::query()->findOrFail($task->field_value_id)->status)->toBe(FieldStatus::Verified)
        ->and(fn () => $apply->handle($task->field_value_id, $task->revision_id, FieldStatus::Verified))->toThrow(InvalidArgumentException::class);
});

it('keeps the answers as the history of a consensus', function () {
    $task = taskFor();
    $users = [answerTask($task, TaskAnswer::Agree), answerTask($task, TaskAnswer::Skip), answerTask($task, TaskAnswer::Disagree)];

    expect(VerificationTask::query()->findOrFail($task->id)->answers()->orderBy('id')->pluck('user_id')->all())
        ->toBe(array_map(fn (User $user): int => $user->id, $users))
        ->and($task->refresh()->resolved_at)->not->toBeNull();
});
