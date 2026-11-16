<?php

namespace App\Modules\Knowledge\Data;

use App\Models\User;
use App\Modules\Community\Enums\Role;

/**
 * Who makes a change: a person (user) or an AI run. Exactly one of the ids is set, matching the
 * one-author rule on revisions. A person carries their role (Community), read when the actor is made;
 * the Knowledge actions check it against the role matrix (`Role`, D36).
 */
final readonly class Actor
{
    private function __construct(
        public ?int $userId,
        public ?int $aiRunId,
        public ?Role $role = null,
    ) {}

    public static function user(User $user): self
    {
        return new self($user->id, null, $user->role);
    }

    /**
     * An AI run from AiPipeline, referenced by id only (D20).
     */
    public static function aiRun(int $aiRunId): self
    {
        return new self(null, $aiRunId);
    }

    public function isAi(): bool
    {
        return $this->aiRunId !== null;
    }

    /**
     * The person's role; an AI run has none.
     */
    public function personRole(): Role
    {
        return $this->role ?? Role::Contributor;
    }

    /**
     * The author columns of a revision.
     *
     * @return array{user_id: int|null, ai_run_id: int|null}
     */
    public function authorColumns(): array
    {
        return ['user_id' => $this->userId, 'ai_run_id' => $this->aiRunId];
    }
}
