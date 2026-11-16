<?php

namespace App\Modules\Knowledge\Actions;

use App\Modules\Knowledge\Data\Actor;
use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Exceptions\StatusTransitionNotAllowed;
use App\Modules\Knowledge\Models\FieldValue;
use Illuminate\Support\Facades\DB;

/**
 * Moves a value slot to another status along FieldStatus::allowedTargets(). The slot needs a current
 * value, and `sourced` needs a source on it. An AI run never changes a status; its revisions get one
 * from RecordRevision. M8 adds the role checks (reviewer for verified, expert for health and legal).
 */
final class ChangeStatus
{
    public function handle(int $fieldValueId, FieldStatus $target, Actor $actor): FieldValue
    {
        if ($actor->isAi()) {
            throw StatusTransitionNotAllowed::because('an AI run cannot change a status');
        }

        return DB::transaction(function () use ($fieldValueId, $target): FieldValue {
            $slot = FieldValue::query()->lockForUpdate()->findOrFail($fieldValueId);
            $current = $slot->currentRevision;

            if ($current === null) {
                throw StatusTransitionNotAllowed::because('the value slot has no current value');
            }

            if (! $slot->status->canMoveTo($target)) {
                throw StatusTransitionNotAllowed::between($slot->status, $target);
            }

            if ($target === FieldStatus::Sourced && ! $current->sources()->exists()) {
                throw StatusTransitionNotAllowed::because('the current value has no source');
            }

            $slot->status = $target;
            $slot->save();

            return $slot;
        });
    }
}
