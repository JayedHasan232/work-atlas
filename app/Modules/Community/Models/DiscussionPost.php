<?php

namespace App\Modules\Community\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A post in a discussion (PLAN M19). The body never changes and the row is never deleted; a moderator
 * can only hide it, and the hide is logged in `discussion_moderations` (D70). `flags` are the
 * promotional-text signals found when it was written (D67).
 *
 * @property int $id
 * @property int $discussion_thread_id
 * @property int $user_id
 * @property string $body
 * @property string $locale
 * @property list<array{reason: string, detail: string|null}> $flags
 * @property Carbon|null $hidden_at
 * @property int|null $hidden_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['discussion_thread_id', 'user_id', 'body', 'locale', 'flags', 'hidden_at', 'hidden_by'])]
final class DiscussionPost extends Model
{
    /** @var array<string, mixed> */
    protected $attributes = ['flags' => '[]'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'flags' => 'array',
            'hidden_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<DiscussionThread, $this>
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(DiscussionThread::class, 'discussion_thread_id');
    }
}
