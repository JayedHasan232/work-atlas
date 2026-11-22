<?php

namespace App\Modules\Community\Actions;

use App\Models\User;
use App\Modules\Community\Exceptions\ModerationNotAllowed;
use App\Modules\Community\Models\RevisionPatrol;
use App\Modules\Knowledge\Queries\Edits;

/**
 * A reviewer marks a person's edit as looked at (PLAN M18), so it leaves the patrol queue. Marking it
 * again changes nothing.
 */
final class MarkPatrolled
{
    public function __construct(private readonly Edits $edits) {}

    public function handle(int $revisionId, User $user): void
    {
        if (! $user->canModerate()) {
            throw ModerationNotAllowed::because("user {$user->id} cannot patrol");
        }

        if ($this->edits->find($revisionId) === null) {
            throw ModerationNotAllowed::because("revision {$revisionId} does not exist");
        }

        RevisionPatrol::query()->insertOrIgnore(['revision_id' => $revisionId, 'user_id' => $user->id]);
    }
}
