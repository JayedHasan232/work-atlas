<?php

namespace App\Modules\Knowledge\Actions;

use App\Modules\Knowledge\Data\Actor;
use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Enums\ReviewDecision;
use App\Modules\Knowledge\Events\CurrentValueChanged;
use App\Modules\Knowledge\Exceptions\RevertNotAllowed;
use App\Modules\Knowledge\Exceptions\SourceRequired;
use App\Modules\Knowledge\Models\FieldValue;
use App\Modules\Knowledge\Models\Revision;
use Illuminate\Support\Facades\DB;

/**
 * Undoes the current value with a new revision (rule 5: history is never changed). The new revision
 * restores what the reverted one replaced: its value, its sources and the slot's status at that time.
 * Reverting the first value of a slot leaves it unknown (null, ai_draft). Only the current revision can
 * be reverted; to go further back, revert again. M8 adds the role check (patrol).
 */
final class RevertRevision
{
    public function handle(int $revisionId, Actor $actor, ?string $comment = null): Revision
    {
        if ($actor->isAi()) {
            throw RevertNotAllowed::because($revisionId, 'an AI run cannot revert');
        }

        return DB::transaction(function () use ($revisionId, $actor, $comment): Revision {
            $reverted = Revision::query()->findOrFail($revisionId);
            $slot = FieldValue::query()->lockForUpdate()->findOrFail($reverted->field_value_id);

            if ($slot->current_revision_id !== $reverted->id) {
                throw RevertNotAllowed::because($revisionId, 'it is not the current value');
            }

            $role = $actor->personRole();

            if (! $role->canRevert($slot->definition->sensitive, $slot->status)) {
                throw RevertNotAllowed::because($revisionId, "a {$role->value} cannot revert this value");
            }

            [$restored, $restoredStatus] = $this->replacedBy($reverted);
            $value = $restored?->new_value;
            $sourceIds = $restored !== null ? $restored->sources()->pluck('sources.id')->all() : [];

            if ($slot->definition->requires_source && $value !== null && $sourceIds === []) {
                throw SourceRequired::forField($slot->definition->key);
            }

            $revision = Revision::query()->create([
                'field_value_id' => $slot->id,
                'old_value' => $reverted->new_value,
                'new_value' => $value,
                ...$actor->authorColumns(),
                'comment' => $comment,
                'reverts_revision_id' => $reverted->id,
                'replaces_revision_id' => $reverted->id,
                'old_status' => $slot->status,
            ]);

            if ($sourceIds !== []) {
                $revision->sources()->attach($sourceIds);
            }

            $slot->current_revision_id = $revision->id;
            $slot->status = $restoredStatus ?? FieldStatus::AiDraft;
            $slot->save();

            event(new CurrentValueChanged($slot->occupation_id, $slot->field_definition_id, $slot->definition->key, $slot->locale, $slot->region, $value));

            return $revision;
        });
    }

    /**
     * The revision and status that were current before `$reverted` became current: on the revision
     * itself when it was written directly, on its accepting review when it was a proposal.
     *
     * @return array{0: Revision|null, 1: FieldStatus|null}
     */
    private function replacedBy(Revision $reverted): array
    {
        $record = $reverted;

        if ($reverted->proposed) {
            $record = $reverted->reviews()->where('decision', ReviewDecision::Accepted)->firstOrFail();
        }

        $restored = $record->replaces_revision_id !== null
            ? Revision::query()->findOrFail($record->replaces_revision_id)
            : null;

        return [$restored, $record->old_status];
    }
}
