<?php

namespace App\Modules\Taxonomy\Actions;

use App\Models\User;
use App\Modules\Taxonomy\Enums\DuplicateStatus;
use App\Modules\Taxonomy\Exceptions\ListReviewNotAllowed;
use App\Modules\Taxonomy\Models\DuplicateCandidate;
use Illuminate\Support\Facades\DB;

/**
 * A reviewer decides that a flagged pair is two different occupations (M11). The pair stays, decided,
 * so the detector never flags it again (D47).
 */
final class KeepSeparate
{
    public function handle(int $pairId, User $user, ?string $note = null): void
    {
        if (! $user->role->canReview()) {
            throw ListReviewNotAllowed::because('Only reviewers and above can review the occupation list.');
        }

        DB::transaction(function () use ($pairId, $user, $note): void {
            $pair = DuplicateCandidate::query()->lockForUpdate()->findOrFail($pairId);

            if ($pair->status !== DuplicateStatus::Open) {
                throw ListReviewNotAllowed::because("This pair was already decided ({$pair->status->value}).");
            }

            $pair->update([
                'status' => DuplicateStatus::KeptSeparate,
                'decided_by_id' => $user->id,
                'decided_at' => now(),
                'decision_note' => $note,
            ]);
        });
    }
}
