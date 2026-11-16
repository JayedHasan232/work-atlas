<?php

namespace App\Modules\Community\Models;

use App\Modules\Community\Enums\TaskAnswer;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One person's answer to one micro-task. Private: only the outcome reaches the page.
 *
 * @property int $id
 * @property int $verification_task_id
 * @property int $user_id
 * @property TaskAnswer $answer
 * @property CarbonImmutable|null $created_at
 */
#[Fillable(['verification_task_id', 'user_id', 'answer'])]
final class VerificationAnswer extends Model
{
    public const null UPDATED_AT = null;

    /**
     * @return BelongsTo<VerificationTask, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(VerificationTask::class, 'verification_task_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['answer' => TaskAnswer::class];
    }
}
