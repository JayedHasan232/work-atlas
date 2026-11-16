<?php

namespace App\Modules\Discovery\Data;

use App\Modules\Discovery\Enums\MatchOutcome;

/**
 * One occupation that matches a person's situation (PLAN M20), with every answer it was checked
 * against. `notes` are limits that do not come from an answer, such as a required licence.
 */
final readonly class OccupationMatch
{
    /**
     * @param  list<Criterion>  $criteria
     * @param  list<string>  $notes
     */
    public function __construct(
        public int $occupationId,
        public array $criteria,
        public array $notes,
    ) {}

    public function count(MatchOutcome $outcome): int
    {
        return count(array_filter($this->criteria, fn (Criterion $criterion): bool => $criterion->outcome === $outcome));
    }

    /** Fitting answers count twice, partly fitting ones once. */
    public function score(): int
    {
        return 2 * $this->count(MatchOutcome::Met) + $this->count(MatchOutcome::Partly);
    }
}
