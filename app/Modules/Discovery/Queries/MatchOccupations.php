<?php

namespace App\Modules\Discovery\Queries;

use App\Modules\Discovery\Data\Criterion;
use App\Modules\Discovery\Data\GuideAnswers;
use App\Modules\Discovery\Data\GuideQuestions;
use App\Modules\Discovery\Data\MatchResult;
use App\Modules\Discovery\Data\OccupationMatch;
use App\Modules\Discovery\Enums\MatchOutcome;
use App\Modules\Knowledge\Data\MatchableValue;
use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Queries\MatchableValues;
use App\Modules\Taxonomy\Queries\ListedOccupationIds;

/**
 * The guided filter (PLAN M20): the listed occupations that match a person's answers, each with the
 * reasons and the data status behind them. A known value that goes against an answer leaves the
 * occupation out; a missing, "unknown" or disputed value never does, it shows as a limit instead
 * (D72). Best matches first: most fitting answers, then fewest unknowns.
 */
final class MatchOccupations
{
    private const array SCHOOLING = ['none', 'read_write', 'primary', 'ssc', 'hsc', 'bachelors', 'masters_or_higher'];

    private const array FIRST_INCOME = ['within_week', 'under_1_month', 'months_1_6', 'months_6_12', 'years_1_3', 'over_3_years'];

    private const array INCOME_SOON = ['within_week' => 0, 'within_month' => 1, 'within_6_months' => 2];

    private const array LEARNING = ['under_1_month', 'months_1_3', 'months_3_12', 'years_1_2', 'years_2_5', 'over_5_years'];

    private const array LEARN_TIME = ['under_1_month' => 0, 'up_to_3_months' => 1, 'up_to_1_year' => 2, 'up_to_2_years' => 3];

    private const array PHYSICAL = ['light' => 2, 'moderate' => 3, 'hard' => 4];

    public function __construct(
        private readonly ListedOccupationIds $occupations,
        private readonly MatchableValues $values,
    ) {}

    public function handle(GuideAnswers $answers, int $limit = 30): MatchResult
    {
        $ids = $this->occupations->handle();
        $values = $this->values->handle($ids, GuideQuestions::fields());
        $conditions = $answers->conditions();
        $matches = [];

        foreach ($ids as $id) {
            $match = $this->match($id, $conditions, $values[$id] ?? []);

            if ($match !== null) {
                $matches[] = $match;
            }
        }

        usort($matches, fn (OccupationMatch $a, OccupationMatch $b): int => [$b->score(), $a->count(MatchOutcome::Unknown), $a->occupationId]
            <=> [$a->score(), $b->count(MatchOutcome::Unknown), $b->occupationId]);

        return new MatchResult(array_slice($matches, 0, $limit), count($matches), count($ids));
    }

    /**
     * @param  array<string, string>  $conditions
     * @param  array<string, MatchableValue>  $values
     */
    private function match(int $occupationId, array $conditions, array $values): ?OccupationMatch
    {
        $criteria = [];

        foreach ($conditions as $question => $answer) {
            $field = GuideQuestions::QUESTIONS[$question]['field'];
            $value = $values[$field] ?? null;
            $known = $value !== null && $value->value !== null && $value->value !== 'unknown' && $value->status !== FieldStatus::Disputed;
            $outcome = $known ? $this->judge($question, $answer, $value->value) : MatchOutcome::Unknown;

            if ($outcome === null) {
                return null;
            }

            $criteria[] = new Criterion($question, $answer, $field, $outcome, $value?->value, $value?->status);
        }

        $notes = [];

        if (($values['formal_training'] ?? null)?->value === 'required') {
            $notes[] = 'training_required';
        }

        if (($values['seasonal'] ?? null)?->value === 'year_round_with_peaks') {
            $notes[] = 'seasonal_peaks';
        }

        return new OccupationMatch($occupationId, $criteria, $notes);
    }

    /**
     * How a known value fares on one answer; null leaves the occupation out.
     */
    private function judge(string $question, string $answer, mixed $value): ?MatchOutcome
    {
        return match ($question) {
            'hours' => match ($value) {
                'yes' => MatchOutcome::Met,
                'sometimes' => MatchOutcome::Partly,
                'no' => null,
                default => MatchOutcome::Unknown,
            },
            'when' => $this->when($answer, $value),
            'income_soon' => $this->withinBand(self::FIRST_INCOME, $value, self::INCOME_SOON[$answer]),
            'education' => $this->education($answer, $value),
            'physical' => is_int($value) ? ($value <= self::PHYSICAL[$answer] ? MatchOutcome::Met : null) : MatchOutcome::Unknown,
            'learn_time' => $this->withinBand(self::LEARNING, $value, self::LEARN_TIME[$answer]),
            'seasonal' => match ($value) {
                'year_round', 'year_round_with_peaks' => MatchOutcome::Met,
                'seasonal_only' => null,
                default => MatchOutcome::Unknown,
            },
            'setting', 'area' => is_array($value) ? (in_array($answer, $value, true) ? MatchOutcome::Met : null) : MatchOutcome::Unknown,
            default => MatchOutcome::Unknown,
        };
    }

    private function when(string $answer, mixed $value): ?MatchOutcome
    {
        if (! is_array($value)) {
            return MatchOutcome::Unknown;
        }

        return match (true) {
            in_array($answer, $value, true), in_array('self_set', $value, true) => MatchOutcome::Met,
            in_array('rotating_shifts', $value, true) => MatchOutcome::Partly,
            default => null,
        };
    }

    /**
     * @param  list<string>  $bands
     */
    private function withinBand(array $bands, mixed $value, int $highest): ?MatchOutcome
    {
        $index = array_search($value, $bands, true);

        if ($index === false) {
            return MatchOutcome::Unknown;
        }

        return $index <= $highest ? MatchOutcome::Met : null;
    }

    /**
     * Schooling compares by level. A vocational certificate is something a person can still earn, so
     * it never leaves an occupation out: it fits in part unless the person has one (D72).
     */
    private function education(string $answer, mixed $value): ?MatchOutcome
    {
        if ($value === 'vocational') {
            return $answer === 'vocational' ? MatchOutcome::Met : MatchOutcome::Partly;
        }

        $required = array_search($value, self::SCHOOLING, true);

        if ($required === false) {
            return MatchOutcome::Unknown;
        }

        $have = array_search($answer === 'vocational' ? 'ssc' : $answer, self::SCHOOLING, true);

        return $have !== false && $required <= $have ? MatchOutcome::Met : null;
    }
}
