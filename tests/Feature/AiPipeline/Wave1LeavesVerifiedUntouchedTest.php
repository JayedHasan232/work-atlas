<?php

use App\Modules\AiPipeline\Actions\GenerateWave1;
use App\Modules\AiPipeline\Enums\Wave1Status;
use App\Modules\Knowledge\Actions\ChangeStatus;
use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Knowledge\Models\FieldValue;
use App\Modules\Knowledge\Models\Revision;

/*
 * Rule 4 in Wave 1 (PLAN M12): neither model replaces a verified value. Model A's answer becomes a
 * proposal for a reviewer, model B's a second opinion, and the verified value stays current.
 */

it('leaves a verified value current and adds both answers as proposals', function () {
    $fake = wave1Setup();
    $occupation = listedOccupation();
    $field = FieldDefinition::query()->where('key', 'physical_workload')->sole();
    $verified = recordValue($field, 2, occupationId: $occupation->id);
    app(ChangeStatus::class)->handle($verified->field_value_id, FieldStatus::Verified, reviewerActor());
    $fake->respondWithJson(wave1Output(['physical_workload' => 5]))->respondWithJson(wave1Output(['physical_workload' => 5]));

    $result = app(GenerateWave1::class)->handle($occupation->id);

    $slot = FieldValue::query()->findOrFail($verified->field_value_id);
    $proposals = Revision::query()->where('field_value_id', $slot->id)->where('proposed', true)->pluck('new_value')->all();

    expect($result->status)->toBe(Wave1Status::Generated)
        ->and($slot->status)->toBe(FieldStatus::Verified)
        ->and($slot->current_revision_id)->toBe($verified->id)
        ->and($slot->currentRevision->new_value)->toBe(2)
        ->and($proposals)->toBe([5, 5])
        ->and($slot->review_priority)->toBe(FieldValue::PRIORITY_PENDING_PROPOSAL);
});
