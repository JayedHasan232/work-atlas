<?php

namespace App\Modules\Knowledge\Queries;

use App\Modules\Knowledge\Data\QueueItem;
use App\Modules\Knowledge\Enums\CrossCheck;
use App\Modules\Taxonomy\Queries\OccupationSlugs;

/**
 * The values two models disagree on, as rows for a CSV file (PLAN M12): the first row is the header.
 */
final class DisagreementReport
{
    public const array HEADER = ['occupation_id', 'occupation', 'field', 'status', 'priority', 'current_value', 'other_values'];

    public function __construct(
        private readonly ReviewQueue $queue,
        private readonly OccupationSlugs $slugs,
    ) {}

    /**
     * @return list<list<string>>
     */
    public function handle(int $limit = 50_000): array
    {
        $items = $this->queue->handle(CrossCheck::Disagreed, $limit);
        $slugs = $this->slugs->handle(array_values(array_unique(array_map(fn (QueueItem $item): int => $item->occupationId, $items))));
        $rows = [self::HEADER];

        foreach ($items as $item) {
            $rows[] = [
                (string) $item->occupationId,
                $slugs[$item->occupationId] ?? '',
                $item->fieldKey,
                $item->status->value,
                (string) $item->priority,
                $this->show($item->currentValue),
                implode(' | ', array_map($this->show(...), $item->proposedValues)),
            ];
        }

        return $rows;
    }

    private function show(mixed $value): string
    {
        return is_string($value) ? $value : (string) json_encode($value, JSON_UNESCAPED_UNICODE);
    }
}
