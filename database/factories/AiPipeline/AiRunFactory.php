<?php

namespace Database\Factories\AiPipeline;

use App\Modules\AiPipeline\Enums\AiRunStatus;
use App\Modules\AiPipeline\Models\AiRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A finished run from the fake driver. Tests never call a real provider.
 *
 * @extends Factory<AiRun>
 */
class AiRunFactory extends Factory
{
    protected $model = AiRun::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider' => 'fake',
            'model' => 'fake-model',
            'prompt_key' => 'wave1/filter_fields',
            'prompt_version' => 1,
            'input_hash' => hash('sha256', fake()->unique()->uuid()),
            'raw_output' => '{}',
            'parsed_output' => [],
            'status' => AiRunStatus::Succeeded,
            'input_tokens' => 0,
            'output_tokens' => 0,
            'cost_usd' => '0',
            'started_at' => now()->subSecond(),
            'finished_at' => now(),
        ];
    }

    public function failed(string $error = 'Fake provider error'): static
    {
        return $this->state([
            'status' => AiRunStatus::Failed,
            'raw_output' => null,
            'parsed_output' => null,
            'error' => $error,
        ]);
    }
}
