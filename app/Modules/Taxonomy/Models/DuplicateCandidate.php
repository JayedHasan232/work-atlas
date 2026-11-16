<?php

namespace App\Modules\Taxonomy\Models;

use App\Modules\Taxonomy\Enums\DuplicateStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Two occupations that may be the same work (M11), found by DetectDuplicates. Stored once per pair,
 * lower id first.
 *
 * @property int $id
 * @property int $occupation_id
 * @property int $other_occupation_id
 * @property string $similarity trigram similarity of the closest names, 0–1
 * @property string $locale
 * @property string $name
 * @property string $other_name
 * @property DuplicateStatus $status
 * @property int|null $decided_by_id
 * @property Carbon|null $decided_at
 * @property string|null $decision_note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['occupation_id', 'other_occupation_id', 'similarity', 'locale', 'name', 'other_name', 'status', 'decided_by_id', 'decided_at', 'decision_note'])]
class DuplicateCandidate extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => DuplicateStatus::class,
            'decided_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Occupation, $this>
     */
    public function occupation(): BelongsTo
    {
        return $this->belongsTo(Occupation::class);
    }

    /**
     * @return BelongsTo<Occupation, $this>
     */
    public function otherOccupation(): BelongsTo
    {
        return $this->belongsTo(Occupation::class, 'other_occupation_id');
    }
}
