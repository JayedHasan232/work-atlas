<?php

namespace App\Modules\Community\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A reviewer looked at a person's edit in the patrol queue (PLAN M18). One per revision; the revision
 * is referenced by id only.
 *
 * @property int $id
 * @property int $revision_id
 * @property int $user_id
 * @property Carbon|null $created_at
 */
#[Fillable(['revision_id', 'user_id'])]
final class RevisionPatrol extends Model
{
    public const null UPDATED_AT = null;
}
