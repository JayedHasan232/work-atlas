<?php

namespace Database\Factories\Knowledge;

use App\Models\User;
use App\Modules\AiPipeline\Models\AiRun;
use App\Modules\Knowledge\Models\FieldValue;
use App\Modules\Knowledge\Models\Revision;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A revision written by a user by default; `byAiRun()` for one written by AI.
 *
 * @extends Factory<Revision>
 */
class RevisionFactory extends Factory
{
    protected $model = Revision::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'field_value_id' => FieldValue::factory(),
            'old_value' => null,
            'new_value' => 3,
            'user_id' => User::factory(),
            'ai_run_id' => null,
            'comment' => null,
            'reverts_revision_id' => null,
        ];
    }

    public function byAiRun(?AiRun $run = null): static
    {
        return $this->state([
            'user_id' => null,
            'ai_run_id' => $run !== null ? $run->id : fn (): int => AiRun::factory()->create()->id,
            'confidence' => 0.5,
        ]);
    }

    /**
     * Waits for a review instead of being the current value.
     */
    public function proposed(): static
    {
        return $this->state(['proposed' => true]);
    }
}
