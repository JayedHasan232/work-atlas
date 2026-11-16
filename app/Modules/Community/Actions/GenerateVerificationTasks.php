<?php

namespace App\Modules\Community\Actions;

use App\Modules\Community\Enums\TaskState;
use App\Modules\Community\Models\VerificationTask;
use App\Modules\Knowledge\Data\VerifiableValue;
use App\Modules\Knowledge\Queries\VerifiableValues;
use App\Modules\Taxonomy\Data\OccupationListItem;
use App\Modules\Taxonomy\Queries\PublicOccupations;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Config;

/**
 * Makes the micro-tasks (PLAN M17, D64): one open yes/no question for every verifiable value of the
 * task waves on a public occupation page, ordered later by the slot's review priority (models that
 * disagreed first). A task whose value changed, or whose occupation is no longer public, goes stale.
 * Safe to run again: one task per revision, ever.
 */
final class GenerateVerificationTasks
{
    private const int BATCH = 500;

    public function __construct(
        private readonly VerifiableValues $values,
        private readonly PublicOccupations $occupations,
    ) {}

    /**
     * @return array{created: int, stale: int}
     */
    public function handle(): array
    {
        $stale = $this->markStale();

        /** @var list<int> $waves */
        $waves = Config::array('atlas.community.task_waves');
        $created = 0;
        $batch = [];

        foreach ($this->values->candidates($waves) as $value) {
            $batch[] = $value;

            if (count($batch) === self::BATCH) {
                $created += $this->insert($batch);
                $batch = [];
            }
        }

        $created += $this->insert($batch);

        return ['created' => $created, 'stale' => $stale];
    }

    /**
     * @param  list<VerifiableValue>  $batch
     */
    private function insert(array $batch): int
    {
        $public = $this->publicIds(array_map(fn (VerifiableValue $value): int => $value->occupationId, $batch));
        $now = now();
        $rows = [];

        foreach ($batch as $value) {
            if (! isset($public[$value->occupationId])) {
                continue;
            }

            $rows[] = [
                'field_value_id' => $value->fieldValueId,
                'revision_id' => $value->revisionId,
                'occupation_id' => $value->occupationId,
                'author_user_id' => $value->authorUserId,
                'priority' => $value->priority,
                'state' => TaskState::Open->value,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return $rows === [] ? 0 : VerificationTask::query()->insertOrIgnore($rows);
    }

    private function markStale(): int
    {
        $stale = 0;

        VerificationTask::query()
            ->where('state', TaskState::Open)
            ->select(['id', 'revision_id', 'occupation_id'])
            ->chunkById(self::BATCH, function (Collection $tasks) use (&$stale): void {
                /** @var Collection<int, VerificationTask> $tasks */
                $current = array_flip($this->values->stillVerifiable(array_values($tasks->pluck('revision_id')->all())));
                $public = $this->publicIds(array_values(array_unique($tasks->pluck('occupation_id')->all())));
                $ids = $tasks
                    ->filter(fn (VerificationTask $task): bool => ! isset($current[$task->revision_id]) || ! isset($public[$task->occupation_id]))
                    ->modelKeys();

                if ($ids !== []) {
                    $stale += VerificationTask::query()->whereKey($ids)->update([
                        'state' => TaskState::Stale->value,
                        'resolved_at' => now(),
                    ]);
                }
            });

        return $stale;
    }

    /**
     * @param  list<int>  $ids
     * @return array<int, true>
     */
    private function publicIds(array $ids): array
    {
        $public = [];

        foreach ($this->occupations->findMany(array_values(array_unique($ids))) as $item) {
            /** @var OccupationListItem $item */
            $public[$item->id] = true;
        }

        return $public;
    }
}
