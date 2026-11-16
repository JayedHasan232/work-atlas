<?php

namespace App\Modules\Community\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One topic on an occupation's talk page (PLAN M19), optionally about one field. The title never
 * changes; a moderator can hide the thread (D70). The occupation is referenced by id only.
 *
 * @property int $id
 * @property int $occupation_id
 * @property string|null $field_key
 * @property string $title
 * @property string $locale
 * @property int $user_id
 * @property int $posts_count
 * @property Carbon|null $last_posted_at
 * @property Carbon|null $hidden_at
 * @property int|null $hidden_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['occupation_id', 'field_key', 'title', 'locale', 'user_id', 'posts_count', 'last_posted_at', 'hidden_at', 'hidden_by'])]
final class DiscussionThread extends Model
{
    /** @var array<string, mixed> */
    protected $attributes = ['posts_count' => 0];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'posts_count' => 'integer',
            'last_posted_at' => 'datetime',
            'hidden_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<DiscussionPost, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(DiscussionPost::class);
    }
}
