<?php

namespace App\Modules\Knowledge\Models;

use App\Modules\Knowledge\Enums\FieldStatus;
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
 * `new_value`. Only Knowledge\Actions change these rows (from M5). The occupation is referenced by id
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
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['occupation_id', 'field_definition_id', 'locale', 'region', 'current_revision_id', 'status', 'review_priority'])]
#[UseFactory(FieldValueFactory::class)]
class FieldValue extends Model
{
    /** @use HasFactory<FieldValueFactory> */
    use HasFactory;

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
