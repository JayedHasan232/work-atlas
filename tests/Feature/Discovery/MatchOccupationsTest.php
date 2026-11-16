<?php

use App\Modules\Community\Enums\Role;
use App\Modules\Discovery\Data\GuideAnswers;
use App\Modules\Discovery\Data\OccupationMatch;
use App\Modules\Discovery\Enums\MatchOutcome;
use App\Modules\Discovery\Queries\MatchOccupations;
use App\Modules\Knowledge\Actions\ChangeStatus;
use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Knowledge\Models\Source;
use App\Modules\Taxonomy\Models\Occupation;

/*
 * PLAN M20, D72: a known value that goes against an answer leaves the occupation out; a missing,
 * "unknown" or disputed value never does. Best matches first.
 */

beforeEach(fn () => wave1Setup());

/**
 * Records Wave 1 values (newest field version) on an occupation, by AI unless an actor is given.
 *
 * @param  array<string, mixed>  $values
 */
function guideValues(Occupation $occupation, array $values): void
{
    foreach ($values as $key => $value) {
        $definition = FieldDefinition::query()->where('key', $key)->orderByDesc('version')->firstOrFail();
        recordValue($definition, $value, occupationId: $occupation->id);
    }
}

/**
 * @param  array<string, string>  $answers
 */
function guideMatchFor(array $answers, Occupation $occupation): ?OccupationMatch
{
    $result = app(MatchOccupations::class)->handle(GuideAnswers::from($answers));

    return collect($result->matches)->first(fn (OccupationMatch $match): bool => $match->occupationId === $occupation->id);
}

it('judges each answer against the known value', function (string $question, string $answer, string $field, mixed $value, ?MatchOutcome $expected) {
    $occupation = listedOccupation();
    guideValues($occupation, [$field => $value]);

    $match = guideMatchFor([$question => $answer], $occupation);

    if ($expected === null) {
        expect($match)->toBeNull();

        return;
    }

    expect($match)->not->toBeNull()
        ->and($match->criteria)->toHaveCount(1)
        ->and($match->criteria[0]->outcome)->toBe($expected)
        ->and($match->criteria[0]->field)->toBe($field);
})->with([
    'part time: yes' => ['hours', 'part_time', 'part_time_possible', 'yes', MatchOutcome::Met],
    'part time: sometimes' => ['hours', 'part_time', 'part_time_possible', 'sometimes', MatchOutcome::Partly],
    'part time: no' => ['hours', 'part_time', 'part_time_possible', 'no', null],
    'night: night work' => ['when', 'night', 'work_time', ['day', 'night'], MatchOutcome::Met],
    'night: own hours' => ['when', 'night', 'work_time', ['self_set'], MatchOutcome::Met],
    'night: rotating shifts' => ['when', 'night', 'work_time', ['rotating_shifts'], MatchOutcome::Partly],
    'night: day only' => ['when', 'night', 'work_time', ['day'], null],
    'income within a month: under a month' => ['income_soon', 'within_month', 'time_to_first_income', 'under_1_month', MatchOutcome::Met],
    'income within a month: within a week' => ['income_soon', 'within_month', 'time_to_first_income', 'within_week', MatchOutcome::Met],
    'income within a month: 1–6 months' => ['income_soon', 'within_month', 'time_to_first_income', 'months_1_6', null],
    'primary schooling: none needed' => ['education', 'primary', 'education_required', 'none', MatchOutcome::Met],
    'primary schooling: SSC needed' => ['education', 'primary', 'education_required', 'ssc', null],
    'primary schooling: vocational needed' => ['education', 'primary', 'education_required', 'vocational', MatchOutcome::Partly],
    'vocational: vocational needed' => ['education', 'vocational', 'education_required', 'vocational', MatchOutcome::Met],
    'vocational: SSC needed' => ['education', 'vocational', 'education_required', 'ssc', MatchOutcome::Met],
    'vocational: HSC needed' => ['education', 'vocational', 'education_required', 'hsc', null],
    'light work: level 2' => ['physical', 'light', 'physical_workload', 2, MatchOutcome::Met],
    'light work: level 3' => ['physical', 'light', 'physical_workload', 3, null],
    'hard work: level 4' => ['physical', 'hard', 'physical_workload', 4, MatchOutcome::Met],
    'hard work: level 5' => ['physical', 'hard', 'physical_workload', 5, null],
    'home based: yes' => ['setting', 'home_based', 'work_setting', ['indoor', 'home_based'], MatchOutcome::Met],
    'home based: no' => ['setting', 'home_based', 'work_setting', ['outdoor'], null],
    'learn in 3 months: 1–3 months' => ['learn_time', 'up_to_3_months', 'time_to_learn', 'months_1_3', MatchOutcome::Met],
    'learn in 3 months: 3–12 months' => ['learn_time', 'up_to_3_months', 'time_to_learn', 'months_3_12', null],
    'year round: with peaks' => ['seasonal', 'year_round', 'seasonal', 'year_round_with_peaks', MatchOutcome::Met],
    'year round: seasonal only' => ['seasonal', 'year_round', 'seasonal', 'seasonal_only', null],
    'rural: yes' => ['area', 'rural', 'available_in', ['rural', 'urban'], MatchOutcome::Met],
    'rural: urban only' => ['area', 'rural', 'available_in', ['urban'], null],
]);

it('adds no condition for an open answer', function () {
    $occupation = listedOccupation();
    guideValues($occupation, ['work_time' => ['day'], 'physical_workload' => 5]);

    $match = guideMatchFor(['when' => 'any', 'physical' => 'any', 'hours' => 'full_day'], $occupation);

    expect($match)->not->toBeNull()->and($match->criteria)->toBe([]);
});

it('ignores answers that are not offered', function () {
    expect(GuideAnswers::from(['hours' => 'forever', 'nonsense' => 'x', 'when' => ['night']])->isEmpty())->toBeTrue()
        ->and(GuideAnswers::from(['when' => 'night', 'x' => 'y'])->answers)->toBe(['when' => 'night']);
});

it('never leaves an occupation out for a missing, unknown or disputed value', function () {
    $missing = listedOccupation();
    $unknown = listedOccupation();
    guideValues($unknown, ['time_to_first_income' => 'unknown']);
    $disputed = listedOccupation();
    guideValues($disputed, ['time_to_first_income' => 'years_1_3']);
    $definition = FieldDefinition::query()->where('key', 'time_to_first_income')->firstOrFail();
    $fieldValue = $definition->values()->where('occupation_id', $disputed->id)->firstOrFail();
    app(ChangeStatus::class)->handle($fieldValue->id, FieldStatus::Disputed, personActor(Role::Maintainer));

    foreach ([$missing, $unknown, $disputed] as $occupation) {
        $match = guideMatchFor(['income_soon' => 'within_week'], $occupation);

        expect($match)->not->toBeNull()
            ->and($match->criteria[0]->outcome)->toBe(MatchOutcome::Unknown);
    }
});

it('puts the best matches first: more fitting answers, then fewer unknowns', function () {
    $partly = listedOccupation();
    guideValues($partly, ['part_time_possible' => 'sometimes', 'work_time' => ['day']]);
    $full = listedOccupation();
    guideValues($full, ['part_time_possible' => 'yes', 'work_time' => ['day']]);
    $unknownTime = listedOccupation();
    guideValues($unknownTime, ['part_time_possible' => 'yes']);
    $excluded = listedOccupation();
    guideValues($excluded, ['part_time_possible' => 'no']);

    $result = app(MatchOccupations::class)->handle(GuideAnswers::from(['hours' => 'part_time', 'when' => 'day']));
    $order = array_map(fn (OccupationMatch $match): int => $match->occupationId, $result->matches);

    expect($order)->toBe([$full->id, $partly->id, $unknownTime->id])
        ->and($result->total)->toBe(3)
        ->and($result->checked)->toBe(4);
});

it('notes required training and seasonal peaks', function () {
    $occupation = listedOccupation();
    guideValues($occupation, ['formal_training' => 'required', 'seasonal' => 'year_round_with_peaks']);

    expect(guideMatchFor(['seasonal' => 'year_round'], $occupation)?->notes)->toBe(['training_required', 'seasonal_peaks']);
});

it('only matches listed occupations', function () {
    $draft = Occupation::factory()->create();
    guideValues($draft, ['part_time_possible' => 'yes']);

    expect(guideMatchFor(['hours' => 'part_time'], $draft))->toBeNull();
});

it('does not match on a sensitive value that is not verified', function () {
    $occupation = listedOccupation();
    $definition = FieldDefinition::factory()->sensitive()->create(['key' => 'physical_workload', 'version' => 99, 'wave' => 1]);
    recordValue($definition, 5, sourceIds: [Source::factory()->create()->id], occupationId: $occupation->id);

    $match = guideMatchFor(['physical' => 'light'], $occupation);

    expect($match)->not->toBeNull()
        ->and($match->criteria[0]->outcome)->toBe(MatchOutcome::Unknown)
        ->and($match->criteria[0]->value)->toBeNull();
});
