<?php

namespace Database\Factories\Community;

use App\Modules\Community\Models\ErrorReport;
use App\Modules\Taxonomy\Models\Occupation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * An open, anonymous report about a whole occupation page.
 *
 * @extends Factory<ErrorReport>
 */
class ErrorReportFactory extends Factory
{
    protected $model = ErrorReport::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'occupation_id' => Occupation::factory(),
            'field_definition_id' => null,
            'locale' => 'bn',
            'message' => fake()->sentence(),
            'user_id' => null,
        ];
    }
}
