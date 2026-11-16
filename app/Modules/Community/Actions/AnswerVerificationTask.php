<?php

namespace App\Modules\Community\Actions;

use App\Modules\Community\Enums\TaskAnswer;
use App\Modules\Community\Enums\TaskState;
use App\Modules\Community\Exceptions\ContributionRejected;
use App\Modules\Community\Jobs\EvaluateConsensusJob;
use App\Modules\Community\Models\VerificationAnswer;
use App\Modules\Community\Models\VerificationTask;

/**
 * One person's answer to a micro-task (PLAN M17): once per task, never about their own value, only
 * while the task is open. An agree or disagree queues the consensus check; a skip only keeps the
 * question from being asked again.
 */
final class AnswerVerificationTask
{
    public function handle(int $taskId, int $userId, TaskAnswer $answer): void
    {
        $task = VerificationTask::query()->find($taskId)
            ?? throw ContributionRejected::because('task', "task {$taskId} does not exist");

        if ($task->state !== TaskState::Open) {
            throw ContributionRejected::because('state', "task {$taskId} is {$task->state->value}");
        }

        if ($task->author_user_id === $userId) {
            throw ContributionRejected::because('own', "user {$userId} wrote the value of task {$taskId}");
        }

        // insertOrIgnore, not a caught unique violation: in PostgreSQL a failed insert would abort the
        // surrounding transaction.
        $inserted = VerificationAnswer::query()->insertOrIgnore([
            'verification_task_id' => $task->id,
            'user_id' => $userId,
            'answer' => $answer->value,
        ]);

        if ($inserted === 0) {
            throw ContributionRejected::because('answered', "user {$userId} already answered task {$taskId}");
        }

        if ($answer !== TaskAnswer::Skip) {
            EvaluateConsensusJob::dispatch($task->id)->afterCommit();
        }
    }
}
