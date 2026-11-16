<?php

namespace App\Modules\Knowledge\Models;

use App\Modules\Knowledge\Enums\CrossCheck;
use App\Modules\Knowledge\Enums\FieldStatus;
use Carbon\CarbonInterface;
use Database\Factories\Knowledge\FieldValueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One value slot: occupation × field × locale × region. The value is the current revision's
 * `new_value`. Only Knowledge\Actions change these rows. The occupation is referenced by id
 * only: Taxonomy's models are private to Taxonomy.
 *
 * @property int $id
 * @property int $occupation_id
 * @property int $field_definition_id
 * @property string|null $locale
 * @property string|null $region
 * @property int|null $current_revision_id
 * @property FieldStatus $status
 * @property int $review_priority
 * @property CrossCheck|null $cross_check
 * @property CarbonInterface|null $cross_checked_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['occupation_id', 'field_definition_id', 'locale', 'region', 'current_revision_id', 'status', 'review_priority', 'cross_check', 'cross_checked_at'])]
#[UseFactory(FieldValueFactory::class)]
class FieldValue extends Model
{
    /** @use HasFactory<FieldValueFactory> */
    use HasFactory;

    /** Nothing waits for a review. */
    public const int PRIORITY_NONE = 0;

    /** A proposed revision waits for a review. */
    public const int PRIORITY_PENDING_PROPOSAL = 10;

    /** Two models gave different values (D50): the top of the queue. */
    public const int PRIORITY_MODELS_DISAGREE = 20;

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'ai_draft',
        'review_priority' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => FieldStatus::class,
            'review_priority' => 'integer',
            'cross_check' => CrossCheck::class,
            'cross_checked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<FieldDefinition, $this>
     */
    public function definition(): BelongsTo
    {
        return $this->belongsTo(FieldDefinition::class, 'field_definition_id');
    }

    /**
     * @return BelongsTo<Revision, $this>
     */
    public function currentRevision(): BelongsTo
    {
        return $this->belongsTo(Revision::class, 'current_revision_id');
    }

    /**
     * @return HasMany<Revision, $this>
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(Revision::class);
    }
}
