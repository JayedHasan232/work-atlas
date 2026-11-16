<?php

namespace App\Modules\Discovery\Data;

/**
 * The guided filter's questions (PLAN M20, A5), in the order they are asked. Each question maps to
 * one Wave 1 field; its options are the answers a person can give. An option listed in `open` sets
 * no condition ("any time", "can wait"). Labels live in `lang/{bn,en}/guide.php`.
 */
final class GuideQuestions
{
    /**
     * @var array<string, array{field: string, options: list<string>, open: list<string>}>
     */
    public const array QUESTIONS = [
        'hours' => ['field' => 'part_time_possible', 'options' => ['full_day', 'part_time'], 'open' => ['full_day']],
        'when' => ['field' => 'work_time', 'options' => ['day', 'evening', 'night', 'any'], 'open' => ['any']],
        'income_soon' => ['field' => 'time_to_first_income', 'options' => ['within_week', 'within_month', 'within_6_months', 'can_wait'], 'open' => ['can_wait']],
        'education' => ['field' => 'education_required', 'options' => ['none', 'read_write', 'primary', 'ssc', 'hsc', 'vocational', 'bachelors', 'masters_or_higher'], 'open' => []],
        'physical' => ['field' => 'physical_workload', 'options' => ['light', 'moderate', 'hard', 'any'], 'open' => ['any']],
        'setting' => ['field' => 'work_setting', 'options' => ['indoor', 'outdoor', 'home_based', 'on_the_move', 'any'], 'open' => ['any']],
        'learn_time' => ['field' => 'time_to_learn', 'options' => ['under_1_month', 'up_to_3_months', 'up_to_1_year', 'up_to_2_years', 'longer'], 'open' => ['longer']],
        'seasonal' => ['field' => 'seasonal', 'options' => ['year_round', 'seasonal_ok'], 'open' => ['seasonal_ok']],
        'area' => ['field' => 'available_in', 'options' => ['rural', 'urban', 'either'], 'open' => ['either']],
    ];

    /**
     * The Wave 1 fields the filter reads, plus `formal_training` for the limits.
     *
     * @return list<string>
     */
    public static function fields(): array
    {
        return [...array_values(array_map(fn (array $question): string => $question['field'], self::QUESTIONS)), 'formal_training'];
    }
}
