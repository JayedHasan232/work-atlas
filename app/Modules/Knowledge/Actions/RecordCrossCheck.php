<?php

namespace App\Modules\Knowledge\Actions;

use App\Modules\Knowledge\Enums\CrossCheck;
use App\Modules\Knowledge\Models\FieldValue;
use Illuminate\Support\Facades\DB;

/**
 * Saves the two-model cross-check on a value slot (D50). A disagreement moves the slot to the top of
 * the review queue; an agreement leaves its place as it is, below every disagreement.
 */
final class RecordCrossCheck
{
    public function __construct(private readonly LockValueSlot $lockSlot) {}

    public function handle(int $occupationId, int $fieldDefinitionId, CrossCheck $result, ?string $locale = null, ?string $region = null): void
    {
        DB::transaction(function () use ($occupationId, $fieldDefinitionId, $result, $locale, $region): void {
            $slot = $this->lockSlot->handle($occupationId, $fieldDefinitionId, $locale, $region);
            $slot->cross_check = $result;
            $slot->cross_checked_at = now();

            if ($result === CrossCheck::Disagreed) {
                $slot->review_priority = max($slot->review_priority, FieldValue::PRIORITY_MODELS_DISAGREE);
            }

            $slot->save();
        });
    }
}
