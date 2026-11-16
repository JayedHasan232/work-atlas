<?php

namespace App\Modules\Knowledge\Actions;

use App\Modules\Knowledge\Data\RevisionInput;
use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Exceptions\InvalidFieldValue;
use App\Modules\Knowledge\Exceptions\SourceRequired;
use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Knowledge\Models\FieldValue;
use App\Modules\Knowledge\Models\Revision;
use App\Modules\Knowledge\Models\Source;
use Illuminate\Support\Facades\DB;

/**
 * The only way a field value changes (D3). In one transaction, with the slot's row locked:
 *
 * - the value must fit the field definition (ValidateFieldValue);
 * - a `requires_source` field needs at least one source for a known value;
 * - an AI run needs a confidence from 0 to 1, a person must not give one;
 * - an AI write on a `verified` value becomes a proposal: the current value stays, the slot moves up
 *   the review queue (rule 4);
 * - any other AI write becomes the current value as `ai_draft`, or `sourced` with a source (rule 3);
 * - a person's write is a proposal until roles exist (M8: contributors propose, trusted contributors
 *   write descriptive fields directly).
 *
 * Throws a KnowledgeRuleViolation, and writes nothing, when a rule is broken.
 */
final class RecordRevision
{
    public function __construct(
        private readonly ValidateFieldValue $validateValue,
        private readonly LockValueSlot $lockSlot,
    ) {}

    public function handle(RevisionInput $input): Revision
    {
        $definition = FieldDefinition::query()->findOrFail($input->fieldDefinitionId);
        $value = $this->validateValue->handle($definition, $input->value, $input->locale, $input->region);
        $this->checkConfidence($definition, $input);
        $sourceIds = $this->checkSources($definition, $value, $input->sourceIds);

        return DB::transaction(function () use ($input, $definition, $value, $sourceIds): Revision {
            $slot = $this->lockSlot->handle($input->occupationId, $input->fieldDefinitionId, $input->locale, $input->region);
            $current = $slot->currentRevision;
            // A verified value changes only through a review. Otherwise an AI write applies at once; a
            // person's applies when their role allows it on this field (Role::appliesDirectly, D36).
            $proposed = $slot->status === FieldStatus::Verified
                || (! $input->actor->isAi() && ! $input->actor->personRole()->appliesDirectly($definition->sensitive));

            $revision = Revision::query()->create([
                'field_value_id' => $slot->id,
                'old_value' => $current?->new_value,
                'new_value' => $value,
                ...$input->actor->authorColumns(),
                'comment' => $input->comment,
                'proposed' => $proposed,
                'confidence' => $input->confidence,
                'replaces_revision_id' => $proposed ? null : $current?->id,
                'old_status' => $proposed || $current === null ? null : $slot->status,
            ]);

            if ($sourceIds !== []) {
                $revision->sources()->attach($sourceIds);
            }

            if ($proposed) {
                $slot->review_priority = max($slot->review_priority, FieldValue::PRIORITY_PENDING_PROPOSAL);
            } else {
                $slot->current_revision_id = $revision->id;
                $slot->status = match (true) {
                    $value !== null && $sourceIds !== [] => FieldStatus::Sourced,
                    $input->actor->isAi() => FieldStatus::AiDraft,
                    default => FieldStatus::CommunitySupported,
                };
            }

            $slot->save();

            return $revision;
        });
    }

    private function checkConfidence(FieldDefinition $definition, RevisionInput $input): void
    {
        $confidence = $input->confidence;

        if (! $input->actor->isAi()) {
            if ($confidence !== null) {
                throw InvalidFieldValue::because($definition->key, 'only an AI run gives a confidence');
            }

            return;
        }

        if ($confidence === null || $confidence < 0 || $confidence > 1) {
            throw InvalidFieldValue::because($definition->key, 'an AI run needs a confidence from 0 to 1');
        }
    }

    /**
     * @param  list<int>  $sourceIds
     * @return list<int>
     */
    private function checkSources(FieldDefinition $definition, mixed $value, array $sourceIds): array
    {
        $sourceIds = array_values(array_unique($sourceIds));

        if ($definition->requires_source && $value !== null && $sourceIds === []) {
            throw SourceRequired::forField($definition->key);
        }

        $found = Source::query()->whereKey($sourceIds)->pluck('id')->all();
        $missing = array_values(array_diff($sourceIds, $found));

        if ($missing !== []) {
            throw SourceRequired::unknownSources($missing);
        }

        return $sourceIds;
    }
}
