<?php

namespace App\Modules\Knowledge\Actions;

use App\Modules\Community\Actions\CountReviewOutcome;
use App\Modules\Community\Enums\Role;
use App\Modules\Knowledge\Data\Actor;
use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Enums\ReviewDecision;
use App\Modules\Knowledge\Events\CurrentValueChanged;
use App\Modules\Knowledge\Exceptions\ReviewNotAllowed;
use App\Modules\Knowledge\Models\FieldValue;
use App\Modules\Knowledge\Models\Revision;
use App\Modules\Knowledge\Models\RevisionReview;
use Illuminate\Support\Facades\DB;

/**
 * A person's decision on a pending proposal, recorded in `revision_reviews` (D1). Accepting makes the
 * proposal the current value with status `verified`, because a reviewer approved it; the review keeps
 * the replaced revision and status so a revert can restore them. Rejecting leaves the value as it is.
 * Once no proposal waits, the slot leaves the review queue. Only a reviewer (or higher) reviews, never
 * their own proposal, and the author's trust counters count the outcome.
 */
final class ReviewRevision
{
    public function __construct(
        private readonly CountReviewOutcome $countReviewOutcome,
    ) {}

    public function handle(int $revisionId, Actor $reviewer, ReviewDecision $decision, ?string $comment = null): RevisionReview
    {
        if ($reviewer->isAi()) {
            throw ReviewNotAllowed::because($revisionId, 'an AI run cannot review');
        }

        return DB::transaction(function () use ($revisionId, $reviewer, $decision, $comment): RevisionReview {
            $revision = Revision::query()->findOrFail($revisionId);
            $slot = FieldValue::query()->lockForUpdate()->findOrFail($revision->field_value_id);

            if (! $revision->proposed) {
                throw ReviewNotAllowed::because($revisionId, 'it is not a proposal');
            }

            if ($revision->reviews()->exists()) {
                throw ReviewNotAllowed::because($revisionId, 'it has already been reviewed');
            }

            $role = $reviewer->personRole();

            if (! $role->canReview()) {
                throw ReviewNotAllowed::because($revisionId, "a {$role->value} cannot review");
            }

            // Nobody reviews their own proposal; the maintainer may while they are the only reviewer (A13).
            if ($revision->user_id !== null && $revision->user_id === $reviewer->userId && $role !== Role::Maintainer) {
                throw ReviewNotAllowed::because($revisionId, 'it is your own proposal');
            }

            $accepted = $decision === ReviewDecision::Accepted;
            $replaces = $accepted ? $slot->current_revision_id : null;

            $review = RevisionReview::query()->create([
                'revision_id' => $revision->id,
                'reviewer_id' => $reviewer->userId,
                'decision' => $decision,
                'comment' => $comment,
                'replaces_revision_id' => $replaces,
                'old_status' => $replaces !== null ? $slot->status : null,
            ]);

            if ($accepted) {
                $slot->current_revision_id = $revision->id;
                $slot->status = FieldStatus::Verified;
            }

            if ($revision->user_id !== null) {
                $this->countReviewOutcome->handle($revision->user_id, $accepted);
            }

            if (! $this->hasPendingProposal($slot)) {
                $slot->review_priority = FieldValue::PRIORITY_NONE;
            }

            $slot->save();

            if ($accepted) {
                event(new CurrentValueChanged($slot->occupation_id, $slot->field_definition_id, $slot->definition->key, $slot->locale, $slot->region, $revision->new_value));
            }

            return $review;
        });
    }

    private function hasPendingProposal(FieldValue $slot): bool
    {
        return Revision::query()
            ->where('field_value_id', $slot->id)
            ->where('proposed', true)
            ->whereDoesntHave('reviews')
            ->exists();
    }
}
