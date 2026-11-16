<?php

namespace Database\Factories\Taxonomy;

use App\Modules\Taxonomy\Enums\PublishState;
use App\Modules\Taxonomy\Models\IscoGroup;
use App\Modules\Taxonomy\Models\Occupation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Occupation>
 */
class OccupationFactory extends Factory
{
    protected $model = Occupation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(3),
            'isco_code' => fn (): string => IscoGroup::factory()->create()->code,
            'bsco_code' => null,
            'publish_state' => PublishState::Candidate,
            'indexable' => false,
            'merged_into_id' => null,
        ];
    }

    public function listed(): static
    {
        return $this->state(['publish_state' => PublishState::Listed]);
    }

    public function published(): static
    {
        return $this->state(['publish_state' => PublishState::Published]);
    }

    public function mergedInto(Occupation $target): static
    {
        return $this->state([
            'publish_state' => PublishState::Merged,
            'merged_into_id' => $target->id,
            'indexable' => false,
        ]);
    }
}
