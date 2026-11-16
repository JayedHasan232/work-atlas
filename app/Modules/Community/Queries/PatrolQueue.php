<?php

namespace App\Modules\Community\Queries;

use App\Modules\Community\Data\PatrolEntry;
use App\Modules\Community\Enums\FlagReason;
use App\Modules\Community\Models\RevisionFlag;
use App\Modules\Community\Models\RevisionPatrol;
use App\Modules\Knowledge\Data\EditRecord;
use App\Modules\Knowledge\Queries\Edits;
use Illuminate\Support\Facades\Config;

/**
 * The patrol queue (PLAN M18, D66): people's edits of the last `atlas.moderation.patrol_days` days
 * that nobody has patrolled yet, flagged ones first, then newest first. It reads at most `SCAN` edits;
 * enough for Phase 1, where people's edits are few.
 */
final class PatrolQueue
{
    private const int PAGE = 200;

    private const int SCAN = 5000;

    public function __construct(
        private readonly Edits $edits,
    ) {}

    /**
     * @return list<PatrolEntry>
     */
    public function handle(int $limit = 50): array
    {
        $since = now()->subDays(Config::integer('atlas.moderation.patrol_days'));
        $flagged = [];
        $others = [];
        $beforeId = null;

        for ($read = 0; $read < self::SCAN; $read += self::PAGE) {
            $page = $this->edits->recent($since, $beforeId, self::PAGE);

            if ($page === []) {
                break;
            }

            $ids = array_map(fn (EditRecord $edit): int => $edit->revisionId, $page);
            $patrolled = array_flip($this->patrolledIds($ids));
            $flags = $this->flags($ids);

            foreach ($page as $edit) {
                if (isset($patrolled[$edit->revisionId])) {
                    continue;
                }

                $entry = new PatrolEntry($edit, $flags[$edit->revisionId] ?? [], false);

                if ($entry->flags !== []) {
                    $flagged[] = $entry;
                } elseif (count($others) < $limit) {
                    $others[] = $entry;
                }
            }

            $beforeId = end($ids);
        }

        return array_slice([...$flagged, ...$others], 0, $limit);
    }

    public function find(int $revisionId): ?PatrolEntry
    {
        $edit = $this->edits->find($revisionId);

        if ($edit === null) {
            return null;
        }

        return new PatrolEntry($edit, $this->flags([$revisionId])[$revisionId] ?? [], $this->patrolledIds([$revisionId]) !== []);
    }

    /**
     * @param  list<int>  $revisionIds
     * @return list<int>
     */
    private function patrolledIds(array $revisionIds): array
    {
        return array_values(RevisionPatrol::query()->whereIn('revision_id', $revisionIds)->pluck('revision_id')->map(fn (mixed $id): int => (int) $id)->all());
    }

    /**
     * @param  list<int>  $revisionIds
     * @return array<int, list<array{reason: FlagReason, detail: string|null}>>
     */
    private function flags(array $revisionIds): array
    {
        $flags = [];

        foreach (RevisionFlag::query()->whereIn('revision_id', $revisionIds)->orderBy('id')->get() as $flag) {
            $flags[$flag->revision_id][] = ['reason' => $flag->reason, 'detail' => $flag->detail];
        }

        return $flags;
    }
}
