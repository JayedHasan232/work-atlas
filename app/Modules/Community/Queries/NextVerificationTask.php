<?php

namespace App\Modules\Community\Queries;

use App\Modules\Community\Data\OpenTask;
use App\Modules\Community\Enums\TaskState;
use App\Modules\Community\Models\VerificationTask;
use App\Modules\Knowledge\Queries\VerifiableValues;
use App\Modules\Taxonomy\Queries\PublicOccupations;
use Illuminate\Database\Eloquent\Builder;

/**
 * The next question for a person (PLAN M17): an open task they have not answered or skipped, about a
 * value they did not write themselves, highest review priority first, then the oldest. A task whose
 * value changed since it was made is passed over (the generator marks it stale).
 */
final class NextVerificationTask
{
    private const int LOOK_AHEAD = 20;

    public function __construct(
        private readonly VerifiableValues $values,
        private readonly PublicOccupations $occupations,
    ) {}

    public function handle(int $userId): ?OpenTask
    {
        $tasks = VerificationTask::query()
            ->where('state', TaskState::Open)
            ->where(fn (Builder $query) => $query->whereNull('author_user_id')->orWhere('author_user_id', '!=', $userId))
            ->whereDoesntHave('answers', fn (Builder $query) => $query->where('user_id', $userId))
            ->orderByDesc('priority')
            ->orderBy('created_at')
            ->orderBy('id')
            ->limit(self::LOOK_AHEAD)
            ->get(['id', 'revision_id', 'occupation_id']);

        foreach ($tasks as $task) {
            $value = $this->values->find($task->revision_id);

            if ($value !== null && $this->occupations->find($task->occupation_id) !== null) {
                return new OpenTask($task->id, $value);
            }
        }

        return null;
    }

    /**
     * How many questions a person has answered (skips not counted).
     */
    public function answeredBy(int $userId): int
    {
        return VerificationTask::query()
            ->whereHas('answers', fn (Builder $query) => $query->where('user_id', $userId)->where('answer', '!=', 'skip'))
            ->count();
    }
}
