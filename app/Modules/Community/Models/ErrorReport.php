<?php

namespace App\Modules\Community\Models;

use App\Modules\Community\Enums\ErrorReportState;
use Carbon\CarbonImmutable;
use Database\Factories\Community\ErrorReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A reader's report that something on an occupation page is wrong (PLAN M16), optionally about one
 * field. No IP address or contact detail is kept; `user_id` is set only when the reader was signed in.
 *
 * @property int $id
 * @property int $occupation_id
 * @property int|null $field_definition_id
 * @property string $locale
 * @property string $message
 * @property int|null $user_id
 * @property ErrorReportState $state
 * @property int|null $handled_by
 * @property CarbonImmutable|null $handled_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['occupation_id', 'field_definition_id', 'locale', 'message', 'user_id'])]
#[UseFactory(ErrorReportFactory::class)]
final class ErrorReport extends Model
{
    /** @use HasFactory<ErrorReportFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['state' => 'open'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'state' => ErrorReportState::class,
            'handled_at' => 'datetime',
        ];
    }
}
