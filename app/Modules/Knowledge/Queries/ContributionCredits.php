<?php

namespace App\Modules\Knowledge\Queries;

use App\Modules\Knowledge\Data\Credit;
use App\Modules\Knowledge\Enums\ReviewDecision;
use App\Modules\Knowledge\Models\Revision;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Credit computed from revisions (PLAN M16). A person's revision counts once it is part of the record:
 * applied at once (their role allowed it) or a proposal a reviewer accepted. A pending or rejected
 * proposal earns no credit yet. AI runs get no credit; the people who checked their output do.
 */
final class ContributionCredits
{
    /**
     * The people credited on one occupation, most changes first.
     *
     * @return list<Credit>
     */
    public function forOccupation(int $occupationId): array
    {
        $rows = $this->credited()
            ->join('field_values', 'field_values.id', '=', 'revisions.field_value_id')
            ->where('field_values.occupation_id', $occupationId)
            ->selectRaw('revisions.user_id, count(*) as contributions')
            ->groupBy('revisions.user_id')
            ->orderByDesc('contributions')
            ->orderBy('revisions.user_id')
            ->toBase()
            ->get();

        return array_values($rows->map(fn (object $row): Credit => new Credit(
            (int) data_get($row, 'user_id'),
            (int) data_get($row, 'contributions'),
        ))->all());
    }

    public function total(int $userId): int
    {
        return $this->credited()->where('revisions.user_id', $userId)->count();
    }

    /**
     * The occupations one person is credited on, most recent first.
     *
     * @return list<int>
     */
    public function occupationsOf(int $userId, int $limit = 50): array
    {
        return array_values(array_map(intval(...), $this->credited()
            ->join('field_values', 'field_values.id', '=', 'revisions.field_value_id')
            ->where('revisions.user_id', $userId)
            ->groupBy('field_values.occupation_id')
            ->orderByRaw('max(revisions.created_at) desc')
            ->limit($limit)
            ->pluck('field_values.occupation_id')
            ->all()));
    }

    /**
     * How many changes a person wrote since a moment, credited or not (Community's daily limits).
     */
    public function writtenSince(int $userId, CarbonInterface $since): int
    {
        return Revision::query()->where('user_id', $userId)->where('created_at', '>=', $since)->count();
    }

    /**
     * @return Builder<Revision>
     */
    private function credited(): Builder
    {
        return Revision::query()
            ->whereNotNull('revisions.user_id')
            ->where(fn (Builder $query) => $query
                ->where('revisions.proposed', false)
                ->orWhereHas('reviews', fn (Builder $reviews) => $reviews->where('decision', ReviewDecision::Accepted)));
    }
}
