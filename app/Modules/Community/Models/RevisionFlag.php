<?php

namespace App\Modules\Community\Models;

use App\Modules\Community\Enums\FlagReason;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A reason why a person's edit may be promotional (PLAN M18, D67). The revision is referenced by id
 * only: Knowledge's models are private to Knowledge.
 *
 * @property int $id
 * @property int $revision_id
 * @property FlagReason $reason
 * @property string|null $detail
 * @property Carbon|null $created_at
 */
#[Fillable(['revision_id', 'reason', 'detail'])]
final class RevisionFlag extends Model
{
    public const null UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['reason' => FlagReason::class];
    }
}
