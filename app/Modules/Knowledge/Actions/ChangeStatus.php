<?php

namespace App\Modules\Knowledge\Actions;

use App\Modules\Knowledge\Data\Actor;
use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Events\FieldStatusChanged;
use App\Modules\Knowledge\Exceptions\StatusTransitionNotAllowed;
use App\Modules\Knowledge\Models\FieldValue;
use Illuminate\Support\Facades\DB;

/**
 * Moves a value slot to another status along FieldStatus::allowedTargets(). The slot needs a current
 * value, and `sourced` needs a source on it. An AI run never changes a status; its revisions get one
 * from RecordRevision. The person's role must allow the target (Role::canSetStatus, D36).
 */
final class ChangeStatus
{
    public function handle(int $fieldValueId, FieldStatus $target, Actor $actor): FieldValue
    {
        if ($actor->isAi()) {
            throw StatusTransitionNotAllowed::because('an AI run cannot change a status');
        }

        return DB::transaction(function () use ($fieldValueId, $target, $actor): FieldValue {
            $slot = FieldValue::query()->lockForUpdate()->findOrFail($fieldValueId);
            $role = $actor->personRole();

            if (! $role->canSetStatus($target, $slot->definition->sensitive)) {
                throw StatusTransitionNotAllowed::because("a {$role->value} cannot set {$target->value} on this field");
            }
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

            $from = $slot->status;
            $slot->status = $target;
            $slot->save();

            event(new FieldStatusChanged($slot->occupation_id, $slot->field_definition_id, $from, $target));

            return $slot;
        });
    }
}
