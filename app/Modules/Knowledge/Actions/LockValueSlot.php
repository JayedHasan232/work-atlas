<?php

namespace App\Modules\Knowledge\Actions;

use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Models\FieldValue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Finds or creates a value slot and locks its row until the surrounding transaction ends, so two
 * writes to the same slot run one after the other. Creating uses `ON CONFLICT DO NOTHING`, so two
 * first writes at once don't trip the unique index. Called by the other Knowledge actions.
 */
final class LockValueSlot
{
    public function handle(int $occupationId, int $fieldDefinitionId, ?string $locale, ?string $region): FieldValue
    {
        $this->requireTransaction();

        $now = now();

        FieldValue::query()->insertOrIgnore([
            'occupation_id' => $occupationId,
            'field_definition_id' => $fieldDefinitionId,
            'locale' => $locale,
            'region' => $region,
            'status' => FieldStatus::AiDraft->value,
            'review_priority' => FieldValue::PRIORITY_NONE,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return FieldValue::query()
            ->where('occupation_id', $occupationId)
            ->where('field_definition_id', $fieldDefinitionId)
            ->where(fn (Builder $query) => $locale === null ? $query->whereNull('locale') : $query->where('locale', $locale))
            ->where(fn (Builder $query) => $region === null ? $query->whereNull('region') : $query->where('region', $region))
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function requireTransaction(): void
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('A value slot can only be locked inside a database transaction.');
        }
    }
}
