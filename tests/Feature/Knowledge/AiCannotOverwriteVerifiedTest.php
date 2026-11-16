<?php

use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Knowledge\Models\FieldValue;
use App\Modules\Knowledge\Models\Source;

// Rule 4: AI never replaces a verified value; its write becomes a proposed revision.

beforeEach(function () {
    $this->field = FieldDefinition::factory()->create();
    $this->first = recordValue($this->field, 2);
    $this->slot = $this->first->fieldValue;
});

it('turns an AI write on a verified value into a proposal', function (bool $withSource) {
    $this->slot->forceFill(['status' => FieldStatus::Verified])->save();
    $sourceIds = $withSource ? [Source::factory()->create()->id] : [];

    $proposal = recordValue($this->field, 4, sourceIds: $sourceIds, occupationId: $this->slot->occupation_id);
    $slot = $this->slot->fresh();

    expect($proposal->proposed)->toBeTrue()
        ->and($proposal->field_value_id)->toBe($slot->id)
        ->and($proposal->old_value)->toBe(2)
        ->and($slot->current_revision_id)->toBe($this->first->id)
        ->and($slot->currentRevision->new_value)->toBe(2)
        ->and($slot->status)->toBe(FieldStatus::Verified)
        ->and($slot->review_priority)->toBe(FieldValue::PRIORITY_PENDING_PROPOSAL);
})->with(['without a source' => false, 'with a source' => true]);

it('lets an AI write replace a value that is not verified', function (FieldStatus $status) {
    $this->slot->forceFill(['status' => $status])->save();

    $revision = recordValue($this->field, 4, occupationId: $this->slot->occupation_id);
    $slot = $this->slot->fresh();

    expect($revision->proposed)->toBeFalse()
        ->and($slot->current_revision_id)->toBe($revision->id)
        ->and($slot->status)->toBe(FieldStatus::AiDraft)
        ->and($slot->review_priority)->toBe(FieldValue::PRIORITY_NONE);
})->with([FieldStatus::AiDraft, FieldStatus::Sourced, FieldStatus::CommunitySupported, FieldStatus::Disputed]);
