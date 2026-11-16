<?php

namespace App\Modules\Knowledge\Models;

use App\Models\User;
use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Models\Concerns\AppendOnly;
use Database\Factories\Knowledge\RevisionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One entry in a field value's history. Append-only (rule 5): never updated or deleted, and a revert
 * is a new revision. A proposed revision waits for a review instead of becoming current (M5).
 * Exactly one author: `user_id` or `ai_run_id` (an AiPipeline run, referenced by id
 * only because AiPipeline's models are private to it).
 *
 * @property int $id
 * @property int $field_value_id
 * @property mixed $old_value
 * @property mixed $new_value
 * @property int|null $user_id
 * @property int|null $ai_run_id
 * @property string|null $comment
 * @property int|null $reverts_revision_id
 * @property bool $proposed
 * @property float|null $confidence
 * @property int|null $replaces_revision_id
 * @property FieldStatus|null $old_status
 * @property Carbon|null $created_at
 */
#[Fillable(['field_value_id', 'old_value', 'new_value', 'user_id', 'ai_run_id', 'comment', 'reverts_revision_id', 'proposed', 'confidence', 'replaces_revision_id', 'old_status'])]
#[UseFactory(RevisionFactory::class)]
class Revision extends Model
{
    use AppendOnly;

    /** @use HasFactory<RevisionFactory> */
    use HasFactory;

    public const null UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_value' => 'json',
            'new_value' => 'json',
            'proposed' => 'boolean',
            'confidence' => 'float',
            'old_status' => FieldStatus::class,
        ];
    }

    /**
     * @return BelongsTo<FieldValue, $this>
     */
    public function fieldValue(): BelongsTo
    {
        return $this->belongsTo(FieldValue::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function reverts(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reverts_revision_id');
    }

    /**
     * The revision that was current when this one became current (direct revisions only).
     *
     * @return BelongsTo<self, $this>
     */
    public function replaces(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaces_revision_id');
    }

    /**
     * @return BelongsToMany<Source, $this>
     */
    public function sources(): BelongsToMany
    {
        return $this->belongsToMany(Source::class)->withPivot('created_at');
    }

    /**
     * @return HasMany<RevisionReview, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(RevisionReview::class);
    }
}
