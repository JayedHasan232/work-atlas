<?php

namespace App\Modules\Knowledge\Models;

use App\Models\User;
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
 * is a new revision. Exactly one author: `user_id` or `ai_run_id` (an AiPipeline run, referenced by id
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
 * @property Carbon|null $created_at
 */
#[Fillable(['field_value_id', 'old_value', 'new_value', 'user_id', 'ai_run_id', 'comment', 'reverts_revision_id'])]
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
