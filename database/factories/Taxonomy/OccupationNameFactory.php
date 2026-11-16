<?php

namespace Database\Factories\Taxonomy;

use App\Modules\Taxonomy\Actions\NormalizeForSearch;
use App\Modules\Taxonomy\Enums\NameType;
use App\Modules\Taxonomy\Models\Occupation;
use App\Modules\Taxonomy\Models\OccupationName;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * An alias by default. The normalized name here is only lower-cased; the real normalizer (D6) comes
 * with search.
 *
 * @extends Factory<OccupationName>
 */
class OccupationNameFactory extends Factory
{
    protected $model = OccupationName::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = 'Test name '.fake()->unique()->lexify('??????');

        return [
            'occupation_id' => Occupation::factory(),
            'locale' => 'en',
            'name' => $name,
            'normalized_name' => mb_strtolower($name),
            'search_key' => mb_strtolower($name),
            'type' => NameType::Alias,
            'region' => null,
        ];
    }

    public function primary(): static
    {
        return $this->state(['type' => NameType::Primary]);
    }

    public function regional(string $region): static
    {
        return $this->state(['type' => NameType::Regional, 'region' => $region]);
    }

    public function bangla(string $name): static
    {
        return $this->state([
            'locale' => 'bn',
            'name' => $name,
            'normalized_name' => mb_strtolower($name),
            'search_key' => app(NormalizeForSearch::class)->handle($name),
        ]);
    }
}
