<?php

namespace App\Modules\Community\Actions;

use App\Models\User;
use App\Modules\Community\Enums\TaskAnswer;
use App\Modules\Community\Enums\TaskState;
use App\Modules\Community\Models\VerificationTask;
use App\Modules\Knowledge\Actions\ApplyConsensus;
use App\Modules\Knowledge\Enums\FieldStatus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * The consensus rule (PLAN M17, A11b): enough agreeing answers (`atlas.community.consensus_agreements`)
 * and no dissent make the value `community_supported`; one disagreeing answer makes it `disputed` and
 * sends it up the review queue. The people who agreed with a reached consensus get a trust counter.
 * If the value changed meanwhile, the task goes stale and nothing changes.
 */
final class EvaluateConsensus
{
    public function __construct(
        private readonly ApplyConsensus $apply,
        private readonly PromoteTrustedContributors $promote,
    ) {}

    public function handle(int $taskId): ?TaskState
    {
        return DB::transaction(function () use ($taskId): ?TaskState {
            $task = VerificationTask::query()->lockForUpdate()->find($taskId);

            if ($task === null || $task->state !== TaskState::Open) {
                return $task?->state;
            }

            $agreeing = $task->answers()->where('answer', TaskAnswer::Agree)->pluck('user_id')->map(fn (mixed $id): int => (int) $id)->all();
            $dissent = $task->answers()->where('answer', TaskAnswer::Disagree)->exists();

            $outcome = match (true) {
                $dissent => TaskState::Disputed,
                count($agreeing) >= Config::integer('atlas.community.consensus_agreements') => TaskState::Supported,
                default => null,
            };

            if ($outcome === null) {
                return TaskState::Open;
            }

            $applied = $this->apply->handle(
                $task->field_value_id,
                $task->revision_id,
                $outcome === TaskState::Supported ? FieldStatus::CommunitySupported : FieldStatus::Disputed,
            );

            $task->state = $applied ? $outcome : TaskState::Stale;
            $task->resolved_at = now()->toImmutable();
            $task->save();

            if ($applied && $outcome === TaskState::Supported) {
                $agreeing = array_values($agreeing);
                User::query()->whereKey($agreeing)->increment('confirmed_answers_count');
                $this->promote->handle($agreeing);
            }

            return $task->state;
        });
    }
}
