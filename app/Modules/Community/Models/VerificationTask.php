<?php

namespace App\Modules\Community\Models;

use App\Modules\Community\Enums\TaskState;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A yes/no question about one value (PLAN M17): the revision that was current when the task was made.
 * The task row and its answers are the record of a consensus: who agreed, who disagreed, and when.
 *
 * @property int $id
 * @property int $field_value_id
 * @property int $revision_id
 * @property int $occupation_id
 * @property int|null $author_user_id
 * @property TaskState $state
 * @property int $priority
 * @property CarbonImmutable|null $resolved_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['field_value_id', 'revision_id', 'occupation_id', 'author_user_id', 'priority'])]
final class VerificationTask extends Model
{
    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['state' => 'open', 'priority' => 0];

    /**
     * @return HasMany<VerificationAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(VerificationAnswer::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'state' => TaskState::class,
            'resolved_at' => 'datetime',
        ];
    }
}
