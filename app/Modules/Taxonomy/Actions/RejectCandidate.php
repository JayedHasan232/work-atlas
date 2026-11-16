<?php

namespace App\Modules\Taxonomy\Actions;

use App\Models\User;
use App\Modules\Taxonomy\Enums\DuplicateStatus;
use App\Modules\Taxonomy\Enums\PublishState;
use App\Modules\Taxonomy\Exceptions\ListReviewNotAllowed;
use App\Modules\Taxonomy\Models\DuplicateCandidate;
use App\Modules\Taxonomy\Models\Occupation;
use Illuminate\Support\Facades\DB;

/**
 * List review (M11): a candidate that is not an occupation of its own (and not a name of another one)
 * becomes `rejected`, with the reason (D46). It stays in the table, so the decision and its AI run
 * are kept. Its open duplicate pairs are dropped. Reviewers and above.
 */
final class RejectCandidate
{
    public function handle(int $occupationId, User $user, string $note): void
    {
        if (! $user->role->canReview()) {
            throw ListReviewNotAllowed::because('Only reviewers and above can review the occupation list.');
        }

        if (trim($note) === '') {
            throw ListReviewNotAllowed::because('A rejection needs a reason.');
        }

        DB::transaction(function () use ($occupationId, $user, $note): void {
            $occupation = Occupation::query()->lockForUpdate()->findOrFail($occupationId);

            if ($occupation->publish_state !== PublishState::Candidate) {
                throw ListReviewNotAllowed::because("Only a candidate can be rejected; this one is {$occupation->publish_state->value}.");
            }

            $occupation->update([
                'publish_state' => PublishState::Rejected,
                'indexable' => false,
                'reviewed_by_id' => $user->id,
                'reviewed_at' => now(),
                'review_note' => trim($note),
            ]);

            DuplicateCandidate::query()
                ->where('status', DuplicateStatus::Open)
                ->where(fn ($query) => $query->where('occupation_id', $occupationId)->orWhere('other_occupation_id', $occupationId))
                ->delete();
        });
    }
}
