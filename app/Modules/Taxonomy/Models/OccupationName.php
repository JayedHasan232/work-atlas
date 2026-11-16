<?php

namespace App\Modules\Taxonomy\Models;

use App\Modules\Taxonomy\Enums\NameType;
use Database\Factories\Taxonomy\OccupationNameFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $occupation_id
 * @property string $locale
 * @property string $name
 * @property string $normalized_name
 * @property NameType $type
 * @property string|null $region
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['occupation_id', 'locale', 'name', 'normalized_name', 'type', 'region'])]
#[UseFactory(OccupationNameFactory::class)]
class OccupationName extends Model
{
    /** @use HasFactory<OccupationNameFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => NameType::class,
        ];
    }

    /**
     * @return BelongsTo<Occupation, $this>
     */
    public function occupation(): BelongsTo
    {
        return $this->belongsTo(Occupation::class);
    }
}
