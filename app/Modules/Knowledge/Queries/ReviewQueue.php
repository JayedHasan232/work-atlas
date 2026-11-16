<?php

namespace App\Modules\Knowledge\Queries;

use App\Modules\Knowledge\Data\QueueItem;
use App\Modules\Knowledge\Enums\CrossCheck;
use App\Modules\Knowledge\Models\FieldValue;
use App\Modules\Knowledge\Models\Revision;

/**
 * The value slots waiting for a reviewer, highest priority first (D50: model disagreements, then
 * pending proposals), oldest first within a priority.
 */
final class ReviewQueue
{
    /**
     * @return list<QueueItem>
     */
    public function handle(?CrossCheck $crossCheck = null, int $limit = 100): array
    {
        $slots = FieldValue::query()
            ->with(['definition:id,key', 'currentRevision:id,new_value'])
            ->where('review_priority', '>', FieldValue::PRIORITY_NONE)
            ->when($crossCheck !== null, fn ($query) => $query->where('cross_check', $crossCheck))
            ->orderByDesc('review_priority')
            ->orderBy('updated_at')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $proposals = Revision::query()
            ->whereIn('field_value_id', $slots->pluck('id'))
            ->where('proposed', true)
            ->whereDoesntHave('reviews')
            ->orderBy('id')
            ->get(['id', 'field_value_id', 'new_value'])
            ->groupBy('field_value_id');

        return array_values($slots->map(fn (FieldValue $slot): QueueItem => new QueueItem(
            fieldValueId: $slot->id,
            occupationId: $slot->occupation_id,
            fieldKey: $slot->definition->key,
            locale: $slot->locale,
            region: $slot->region,
            status: $slot->status,
            priority: $slot->review_priority,
            crossCheck: $slot->cross_check,
            currentValue: $slot->currentRevision?->new_value,
            proposedValues: array_values($proposals->get($slot->id)?->pluck('new_value')->all() ?? []),
        ))->all());
    }

    /**
     * Why a slot waits for a reviewer, from its priority: `models_disagree`, `community_dissent`,
     * `pending_proposal`, or null when nothing waits.
     */
    public static function reason(int $priority): ?string
    {
        return match (true) {
            $priority >= FieldValue::PRIORITY_MODELS_DISAGREE => 'models_disagree',
            $priority >= FieldValue::PRIORITY_COMMUNITY_DISSENT => 'community_dissent',
            $priority >= FieldValue::PRIORITY_PENDING_PROPOSAL => 'pending_proposal',
            default => null,
        };
    }
}
