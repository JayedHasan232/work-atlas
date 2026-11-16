<?php

namespace App\Modules\Community\Actions;

use App\Models\User;
use App\Modules\Community\Exceptions\ModerationNotAllowed;
use App\Modules\Knowledge\Actions\ChangeStatus;
use App\Modules\Knowledge\Actions\RevertRevision;
use App\Modules\Knowledge\Actions\ReviewRevision;
use App\Modules\Knowledge\Data\Actor;
use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Enums\ReviewDecision;
use App\Modules\Knowledge\Queries\Edits;
use Illuminate\Support\Facades\DB;

/**
 * What a reviewer does from the moderation pages (PLAN M18, D66): decide a proposal, set a value's
 * status, or revert an edit. The Knowledge actions keep their own role rules (a sensitive value
 * needs a reviewer, and so on) and throw a KnowledgeRuleViolation when one is broken. Each decision
 * also marks the edit as patrolled.
 */
final class ModerateEdit
{
    public function __construct(
        private readonly Edits $edits,
        private readonly ReviewRevision $review,
        private readonly ChangeStatus $changeStatus,
        private readonly RevertRevision $revert,
        private readonly MarkPatrolled $patrol,
    ) {}

    public function review(int $fieldValueId, int $revisionId, User $user, ReviewDecision $decision, ?string $comment = null): void
    {
        $this->authorize($user);
        $edit = $this->edits->find($revisionId);

        if ($edit === null || $edit->fieldValueId !== $fieldValueId) {
            throw ModerationNotAllowed::because("revision {$revisionId} is not a proposal for value {$fieldValueId}");
        }

        DB::transaction(function () use ($revisionId, $user, $decision, $comment): void {
            $this->review->handle($revisionId, Actor::user($user), $decision, $this->text($comment));
            $this->patrol->handle($revisionId, $user);
        });
    }

    public function setStatus(int $fieldValueId, FieldStatus $status, User $user): void
    {
        $this->authorize($user);
        $this->changeStatus->handle($fieldValueId, $status, Actor::user($user));
    }

    public function revert(int $revisionId, User $user, ?string $comment = null): int
    {
        $this->authorize($user);

        return DB::transaction(function () use ($revisionId, $user, $comment): int {
            $revision = $this->revert->handle($revisionId, Actor::user($user), $this->text($comment));
            $this->patrol->handle($revisionId, $user);
            $this->patrol->handle($revision->id, $user);

            return $revision->id;
        });
    }

    private function authorize(User $user): void
    {
        if (! $user->canModerate()) {
            throw ModerationNotAllowed::because("user {$user->id} cannot moderate");
        }
    }

    private function text(?string $text): ?string
    {
        $text = $text === null ? null : trim($text);

        return $text === '' ? null : $text;
    }
}
