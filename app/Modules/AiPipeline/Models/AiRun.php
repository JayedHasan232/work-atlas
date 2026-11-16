<?php

namespace App\Modules\AiPipeline\Models;

use App\Modules\AiPipeline\Enums\AiRunStatus;
use Database\Factories\AiPipeline\AiRunFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One AI model call, failures included. Only AiPipeline\AiGateway writes these (from M9).
 *
 * @property int $id
 * @property string $provider
 * @property string $model
 * @property string $prompt_key
 * @property int $prompt_version
 * @property string $input_hash
 * @property string|null $raw_output
 * @property array<array-key, mixed>|null $parsed_output
 * @property AiRunStatus $status
 * @property int|null $input_tokens
 * @property int|null $output_tokens
 * @property string|null $cost_usd
 * @property string|null $batch_id
 * @property string|null $error
 * @property Carbon|null $started_at
 * @property Carbon|null $finished_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'provider', 'model', 'prompt_key', 'prompt_version', 'input_hash', 'raw_output', 'parsed_output',
    'status', 'input_tokens', 'output_tokens', 'cost_usd', 'batch_id', 'error', 'started_at', 'finished_at',
])]
#[UseFactory(AiRunFactory::class)]
class AiRun extends Model
{
    /** @use HasFactory<AiRunFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'pending',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'prompt_version' => 'integer',
            'parsed_output' => 'array',
            'status' => AiRunStatus::class,
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'cost_usd' => 'decimal:6',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
