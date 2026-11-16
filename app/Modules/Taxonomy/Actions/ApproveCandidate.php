<?php

namespace App\Modules\Taxonomy\Actions;

use App\Models\User;
use App\Modules\Taxonomy\Enums\NameType;
use App\Modules\Taxonomy\Enums\PublishState;
use App\Modules\Taxonomy\Exceptions\ListReviewNotAllowed;
use App\Modules\Taxonomy\Models\Occupation;
use App\Modules\Taxonomy\Models\OccupationName;
use Illuminate\Support\Facades\DB;

/**
 * List review (M11): a candidate, or a rejected occupation looked at again, becomes `listed`. It needs
 * a primary name in Bangla and in English. Reviewers and above.
 */
final class ApproveCandidate
{
    public function handle(int $occupationId, User $user, ?string $note = null): void
    {
        if (! $user->role->canReview()) {
            throw ListReviewNotAllowed::because('Only reviewers and above can review the occupation list.');
        }

        DB::transaction(function () use ($occupationId, $user, $note): void {
            $occupation = Occupation::query()->lockForUpdate()->findOrFail($occupationId);

            if (! in_array($occupation->publish_state, [PublishState::Candidate, PublishState::Rejected], true)) {
                throw ListReviewNotAllowed::because("Only a candidate or a rejected occupation can be approved; this one is {$occupation->publish_state->value}.");
            }

            $locales = OccupationName::query()->where('occupation_id', $occupationId)->where('type', NameType::Primary)->pluck('locale')->all();

            foreach (['bn', 'en'] as $locale) {
                if (! in_array($locale, $locales, true)) {
                    throw ListReviewNotAllowed::because("An occupation needs a primary name in {$locale} before it is listed.");
                }
            }

            $occupation->update([
                'publish_state' => PublishState::Listed,
                'reviewed_by_id' => $user->id,
                'reviewed_at' => now(),
                'review_note' => $note,
            ]);
        });
    }
}
