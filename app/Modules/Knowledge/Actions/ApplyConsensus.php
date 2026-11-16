<?php

namespace App\Modules\Knowledge\Actions;

use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Events\FieldStatusChanged;
use App\Modules\Knowledge\Models\FieldValue;
use App\Modules\Knowledge\Queries\VerifiableValues;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * The outcome of a micro-task (PLAN M17, A11b): enough people agreed with no dissent, so the value
 * becomes `community_supported`; or someone disagreed, so it becomes `disputed` and moves up the
 * review queue. It applies only to the revision that was asked about, while it is still current and
 * still verifiable (`ai_draft` or `sourced`, not sensitive); otherwise it changes nothing and returns
 * false, and the task is stale.
 */
final class ApplyConsensus
{
    public function handle(int $fieldValueId, int $revisionId, FieldStatus $outcome): bool
    {
        if (! in_array($outcome, [FieldStatus::CommunitySupported, FieldStatus::Disputed], true)) {
            throw new InvalidArgumentException("a consensus cannot set {$outcome->value}");
        }

        return DB::transaction(function () use ($fieldValueId, $revisionId, $outcome): bool {
            $slot = FieldValue::query()->with('definition')->lockForUpdate()->find($fieldValueId);

            if ($slot === null
                || $slot->current_revision_id !== $revisionId
                || $slot->region !== null
                || $slot->definition->sensitive
                || ! in_array($slot->status, VerifiableValues::STATUSES, true)) {
                return false;
            }

            $from = $slot->status;
            $slot->status = $outcome;

            if ($outcome === FieldStatus::Disputed) {
                $slot->review_priority = max($slot->review_priority, FieldValue::PRIORITY_COMMUNITY_DISSENT);
            }

            $slot->save();

            event(new FieldStatusChanged($slot->occupation_id, $slot->field_definition_id, $from, $outcome));

            return true;
        });
    }
}
