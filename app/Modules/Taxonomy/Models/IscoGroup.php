<?php

namespace App\Modules\Taxonomy\Models;

use Database\Factories\Taxonomy\IscoGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One ISCO-08 group: major (level 1) down to unit group (level 4).
 *
 * @property int $id
 * @property string $code
 * @property int $level
 * @property string|null $parent_code
 * @property string $title_en
 * @property string|null $title_bn
 * @property string|null $bsco_code
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['code', 'level', 'parent_code', 'title_en', 'title_bn', 'bsco_code'])]
#[UseFactory(IscoGroupFactory::class)]
class IscoGroup extends Model
{
    /** @use HasFactory<IscoGroupFactory> */
    use HasFactory;

    public const int UNIT_GROUP_LEVEL = 4;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_code', 'code');
    }

    /**
     * @return HasMany<self, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_code', 'code');
    }

    /**
     * @return HasMany<Occupation, $this>
     */
    public function occupations(): HasMany
    {
        return $this->hasMany(Occupation::class, 'isco_code', 'code');
    }
}
