<?php

namespace App\Modules\Knowledge\Data;

use App\Models\User;

/**
 * Who makes a change: a person (user) or an AI run. Exactly one of the ids is set, matching the
 * one-author rule on revisions. Roles (contributor, trusted, reviewer, expert) arrive with Community in
 * M8; until then every person is treated the same, and the actions note where roles will apply.
 */
final readonly class Actor
{
    private function __construct(
        public ?int $userId,
        public ?int $aiRunId,
    ) {}

    public static function user(User $user): self
    {
        return new self($user->id, null);
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
     * The author columns of a revision.
     *
     * @return array{user_id: int|null, ai_run_id: int|null}
     */
    public function authorColumns(): array
    {
        return ['user_id' => $this->userId, 'ai_run_id' => $this->aiRunId];
    }
}
