<?php

namespace App\Modules\Knowledge\Queries;

use App\Modules\Knowledge\Data\CheckableValue;
use App\Modules\Knowledge\Data\VerifiableValue;
use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Enums\FieldType;
use App\Modules\Knowledge\Models\FieldValue;
use Generator;
use Illuminate\Database\Eloquent\Builder;

/**
 * The values micro-tasks may ask about (PLAN M17, D64): the current national value of a field that is
 * not sensitive and not a name list, while it is still `ai_draft` or `sourced`. Sensitive values are
 * checked by reviewers, and a value further up the ladder needs no more yes/no answers.
 */
final class VerifiableValues
{
    public const array STATUSES = [FieldStatus::AiDraft, FieldStatus::Sourced];

    /**
     * Every verifiable value in the given waves, by slot id.
     *
     * @param  list<int>  $waves
     * @return Generator<int, VerifiableValue>
     */
    public function candidates(array $waves): Generator
    {
        $slots = $this->query()
            ->whereHas('definition', fn (Builder $query) => $query->whereIn('wave', $waves))
            ->with('currentRevision:id,user_id')
            ->select(['id', 'occupation_id', 'current_revision_id', 'review_priority']);

        foreach ($slots->lazyById(500) as $slot) {
            if ($slot->currentRevision === null) {
                continue;
            }

            yield new VerifiableValue(
                fieldValueId: $slot->id,
                revisionId: $slot->currentRevision->id,
                occupationId: $slot->occupation_id,
                authorUserId: $slot->currentRevision->user_id,
                priority: $slot->review_priority,
            );
        }
    }

    /**
     * Of the given revisions, those that are still the current value of a verifiable slot.
     *
     * @param  list<int>  $revisionIds
     * @return list<int>
     */
    public function stillVerifiable(array $revisionIds): array
    {
        if ($revisionIds === []) {
            return [];
        }

        return array_values(array_map('intval', $this->query()
            ->whereIn('current_revision_id', $revisionIds)
            ->pluck('current_revision_id')
            ->all()));
    }

    /**
     * The revision with its field, if it is still the current value of a verifiable slot.
     */
    public function find(int $revisionId): ?CheckableValue
    {
        $slot = $this->query()
            ->where('current_revision_id', $revisionId)
            ->with(['definition', 'currentRevision:id,new_value'])
            ->first();

        if ($slot === null || $slot->currentRevision === null) {
            return null;
        }

        return new CheckableValue(
            fieldValueId: $slot->id,
            revisionId: $revisionId,
            occupationId: $slot->occupation_id,
            definition: $slot->definition->toData(),
            value: $slot->currentRevision->new_value,
            locale: $slot->locale,
            status: $slot->status,
        );
    }

    /**
     * @return Builder<FieldValue>
     */
    private function query(): Builder
    {
        return FieldValue::query()
            ->whereNull('region')
            ->whereNotNull('current_revision_id')
            ->whereIn('status', self::STATUSES)
            ->whereHas('definition', fn (Builder $query) => $query
                ->where('sensitive', false)
                ->whereNot('type', FieldType::NameList));
    }
}
