<?php

use App\Modules\Knowledge\Actions\ApplyConsensus;
use App\Modules\Knowledge\Actions\ReviewRevision;
use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Enums\ReviewDecision;
use App\Modules\Knowledge\Exceptions\ReviewNotAllowed;
use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Knowledge\Models\FieldValue;

beforeEach(function () {
    $this->field = FieldDefinition::factory()->create();
    $this->current = recordValue($this->field, 2);
    $this->slot = $this->current->fieldValue;
    $this->proposal = recordValue($this->field, 4, personActor(), occupationId: $this->slot->occupation_id);
});

function review(int $revisionId, ReviewDecision $decision, mixed $reviewer = null): mixed
{
    return app(ReviewRevision::class)->handle($revisionId, $reviewer ?? reviewerActor(), $decision, 'Checked.');
}

it('makes an accepted proposal the current, verified value', function () {
    $review = review($this->proposal->id, ReviewDecision::Accepted);
    $slot = $this->slot->fresh();

    expect($slot->current_revision_id)->toBe($this->proposal->id)
        ->and($slot->status)->toBe(FieldStatus::Verified)
        ->and($slot->review_priority)->toBe(FieldValue::PRIORITY_NONE)
        ->and($review->decision)->toBe(ReviewDecision::Accepted)
        ->and($review->replaces_revision_id)->toBe($this->current->id)
        ->and($review->old_status)->toBe(FieldStatus::AiDraft);
});

it('keeps the current value when a proposal is rejected', function () {
    $review = review($this->proposal->id, ReviewDecision::Rejected);
    $slot = $this->slot->fresh();

    expect($slot->current_revision_id)->toBe($this->current->id)
        ->and($slot->status)->toBe(FieldStatus::AiDraft)
        ->and($slot->review_priority)->toBe(FieldValue::PRIORITY_NONE)
        ->and($review->replaces_revision_id)->toBeNull()
        ->and($review->old_status)->toBeNull();
});

it('keeps the slot in the queue while another proposal waits', function () {
    recordValue($this->field, 5, personActor(), occupationId: $this->slot->occupation_id);

    review($this->proposal->id, ReviewDecision::Rejected);

    expect($this->slot->fresh()->review_priority)->toBe(FieldValue::PRIORITY_PENDING_PROPOSAL);
});

it('reviews a proposal only once', function () {
    review($this->proposal->id, ReviewDecision::Rejected);

    expect(fn () => review($this->proposal->id, ReviewDecision::Accepted))
        ->toThrow(ReviewNotAllowed::class, 'already been reviewed');
});

it('reviews only proposals', function () {
    expect(fn () => review($this->current->id, ReviewDecision::Accepted))
        ->toThrow(ReviewNotAllowed::class, 'not a proposal');
});

it('never lets an AI run review', function () {
    expect(fn () => review($this->proposal->id, ReviewDecision::Accepted, aiActor()))
        ->toThrow(ReviewNotAllowed::class, 'AI run');

    expect($this->proposal->reviews()->count())->toBe(0);
});

it('keeps a value the community disputes in the queue when a proposal on it is rejected', function () {
    $field = FieldDefinition::factory()->create();
    $current = recordValue($field, 2);
    $slot = $current->fieldValue;
    app(ApplyConsensus::class)->handle($slot->id, $current->id, FieldStatus::Disputed);
    $proposal = recordValue($field, 4, personActor(), occupationId: $slot->occupation_id);

    review($proposal->id, ReviewDecision::Rejected);

    // Still disputed, and micro-tasks skip disputed values: without the queue nobody would look again.
    expect($slot->fresh()->status)->toBe(FieldStatus::Disputed)
        ->and($slot->fresh()->review_priority)->toBe(FieldValue::PRIORITY_COMMUNITY_DISSENT);
});
