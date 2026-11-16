<?php

namespace App\Modules\Taxonomy\Models;

use App\Modules\Taxonomy\Enums\PublishState;
use Database\Factories\Taxonomy\OccupationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $slug
 * @property string $isco_code
 * @property string|null $bsco_code
 * @property PublishState $publish_state
 * @property bool $indexable
 * @property int|null $merged_into_id
 * @property int|null $ai_run_id the AI run that proposed it (D45)
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['slug', 'isco_code', 'bsco_code', 'publish_state', 'indexable', 'merged_into_id', 'ai_run_id'])]
#[UseFactory(OccupationFactory::class)]
class Occupation extends Model
{
    /** @use HasFactory<OccupationFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = [
        'publish_state' => 'candidate',
        'indexable' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'publish_state' => PublishState::class,
            'indexable' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<IscoGroup, $this>
     */
    public function iscoGroup(): BelongsTo
    {
        return $this->belongsTo(IscoGroup::class, 'isco_code', 'code');
    }

    /**
     * @return HasMany<OccupationName, $this>
     */
    public function names(): HasMany
    {
        return $this->hasMany(OccupationName::class);
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function mergedInto(): BelongsTo
    {
        return $this->belongsTo(self::class, 'merged_into_id');
    }
}
