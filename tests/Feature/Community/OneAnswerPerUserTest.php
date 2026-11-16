<?php

use App\Models\User;
use App\Modules\Community\Actions\AnswerVerificationTask;
use App\Modules\Community\Actions\GenerateVerificationTasks;
use App\Modules\Community\Enums\Role;
use App\Modules\Community\Enums\TaskAnswer;
use App\Modules\Community\Exceptions\ContributionRejected;
use App\Modules\Community\Models\VerificationAnswer;
use App\Modules\Community\Models\VerificationTask;
use App\Modules\Community\Queries\NextVerificationTask;
use App\Modules\Knowledge\Actions\RecordSource;
use App\Modules\Knowledge\Data\Actor;
use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Knowledge\Models\FieldValue;

/*
 * PLAN M17: each person answers a question once, never about a value they wrote themselves, and a
 * question they answered (or skipped) is not shown to them again.
 */

beforeEach(function () {
    wave1Setup();
});

function rejectionReason(Closure $answer): ?string
{
    try {
        $answer();
    } catch (ContributionRejected $e) {
        return $e->reason;
    }

    return null;
}

it('takes one answer per person and question', function () {
    $task = taskFor();
    $user = answerTask($task, TaskAnswer::Skip);

    expect(rejectionReason(fn () => answerTask($task, TaskAnswer::Agree, $user)))->toBe('answered')
        ->and(rejectionReason(fn () => answerTask($task, TaskAnswer::Disagree, $user)))->toBe('answered')
        ->and(VerificationAnswer::query()->where('user_id', $user->id)->sole()->answer)->toBe(TaskAnswer::Skip);
});

it('does not count one person twice towards consensus', function () {
    $task = taskFor();
    $user = answerTask($task, TaskAnswer::Agree);
    rejectionReason(fn () => answerTask($task, TaskAnswer::Agree, $user));
    rejectionReason(fn () => answerTask($task, TaskAnswer::Agree, $user));

    expect($task->refresh()->answers()->count())->toBe(1)
        ->and($task->state->value)->toBe('open');
});

it('refuses an answer about a value the person wrote', function () {
    $author = User::factory()->role(Role::Trusted)->create();
    $source = app(RecordSource::class)->handle(fixtureSource());
    $occupation = listedOccupation();
    $revision = recordValue(FieldDefinition::query()->where('key', 'physical_workload')->sole(), 4, Actor::user($author), [$source], $occupation->id);
    app(GenerateVerificationTasks::class)->handle();
    $task = VerificationTask::query()->where('revision_id', $revision->id)->sole();

    expect($task->author_user_id)->toBe($author->id)
        ->and(rejectionReason(fn () => answerTask($task, TaskAnswer::Agree, $author)))->toBe('own')
        ->and(app(NextVerificationTask::class)->handle($author->id))->toBeNull();
});

it('refuses an answer to a task that does not exist', function () {
    $user = User::factory()->create();

    expect(rejectionReason(fn () => app(AnswerVerificationTask::class)->handle(999999, $user->id, TaskAnswer::Agree)))->toBe('task');
});

it('does not show a question again after it was answered or skipped', function () {
    $occupation = listedOccupation();
    $first = taskFor('physical_workload', occupation: $occupation);
    $second = taskFor('mental_workload', occupation: $occupation);
    $user = User::factory()->create();
    $next = app(NextVerificationTask::class);

    $shown = $next->handle($user->id);
    answerTask(VerificationTask::query()->findOrFail($shown?->taskId), TaskAnswer::Skip, $user);
    $then = $next->handle($user->id);
    answerTask(VerificationTask::query()->findOrFail($then?->taskId), TaskAnswer::Agree, $user);

    expect([$shown?->taskId, $then?->taskId])->toEqualCanonicalizing([$first->id, $second->id])
        ->and($next->handle($user->id))->toBeNull()
        ->and($next->answeredBy($user->id))->toBe(1);
});

it('asks about the values that most need a check first', function () {
    $occupation = listedOccupation();
    $calm = taskFor('physical_workload', occupation: $occupation);
    $urgent = taskFor('mental_workload', occupation: $occupation);
    FieldValue::query()->whereKey($urgent->field_value_id)->update(['review_priority' => 20]);
    $urgent->update(['priority' => 20]);

    expect(app(NextVerificationTask::class)->handle(User::factory()->create()->id)?->taskId)->toBe($urgent->id)
        ->and($calm->id)->toBeLessThan($urgent->id);
});
