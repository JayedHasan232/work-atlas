<?php

namespace Database\Factories\Knowledge;

use App\Modules\Knowledge\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A made-up source on a reserved test domain, so no test points to a real page.
 *
 * @extends Factory<Source>
 */
class SourceFactory extends Factory
{
    protected $model = Source::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'url' => 'https://source.example.test/'.fake()->unique()->slug(),
            'publisher' => 'Example publisher',
            'title' => fake()->sentence(4),
            'license' => 'CC-BY-4.0',
            'accessed_on' => now()->toDateString(),
            'archive_url' => null,
        ];
    }
}
