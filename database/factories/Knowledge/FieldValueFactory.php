<?php

namespace Database\Factories\Knowledge;

use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Knowledge\Models\FieldValue;
use App\Modules\Taxonomy\Models\Occupation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * An empty slot (no revision yet). Tests that need a value add a revision; from M5 they go through
 * RecordRevision instead.
 *
 * @extends Factory<FieldValue>
 */
class FieldValueFactory extends Factory
{
    protected $model = FieldValue::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'occupation_id' => fn (): int => Occupation::factory()->create()->id,
            'field_definition_id' => FieldDefinition::factory(),
            'locale' => null,
            'region' => null,
            'current_revision_id' => null,
            'status' => FieldStatus::AiDraft,
            'review_priority' => 0,
        ];
    }
}
