<?php

namespace App\Modules\Community\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One hide or show of a thread or a post by a moderator (PLAN M19, D70). Append-only.
 *
 * @property int $id
 * @property int|null $discussion_thread_id
 * @property int|null $discussion_post_id
 * @property string $action
 * @property string|null $reason
 * @property int $user_id
 * @property Carbon|null $created_at
 */
#[Fillable(['discussion_thread_id', 'discussion_post_id', 'action', 'reason', 'user_id'])]
final class DiscussionModeration extends Model
{
    public const null UPDATED_AT = null;
}
