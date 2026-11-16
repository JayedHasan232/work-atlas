<?php

namespace App\Modules\Knowledge\Models;

use App\Models\User;
use App\Modules\Knowledge\Enums\ReviewDecision;
use App\Modules\Knowledge\Models\Concerns\AppendOnly;
use Database\Factories\Knowledge\RevisionReviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A reviewer's decision on a revision (D1). Append-only: a changed mind is a new review.
 *
 * @property int $id
 * @property int $revision_id
 * @property int $reviewer_id
 * @property ReviewDecision $decision
 * @property string|null $comment
 * @property Carbon|null $created_at
 */
#[Fillable(['revision_id', 'reviewer_id', 'decision', 'comment'])]
#[UseFactory(RevisionReviewFactory::class)]
class RevisionReview extends Model
{
    use AppendOnly;

    /** @use HasFactory<RevisionReviewFactory> */
    use HasFactory;

    public const null UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'decision' => ReviewDecision::class,
        ];
    }

    /**
     * @return BelongsTo<Revision, $this>
     */
    public function revision(): BelongsTo
    {
        return $this->belongsTo(Revision::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
