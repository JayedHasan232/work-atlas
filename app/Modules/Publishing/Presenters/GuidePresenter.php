<?php

namespace App\Modules\Publishing\Presenters;

use App\Modules\Discovery\Data\Criterion;
use App\Modules\Discovery\Data\GuideAnswers;
use App\Modules\Discovery\Data\GuideQuestions;
use App\Modules\Discovery\Data\OccupationMatch;
use App\Modules\Discovery\Enums\MatchOutcome;
use App\Modules\Discovery\Queries\MatchOccupations;
use App\Modules\Knowledge\Data\FieldDefinitionData;
use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Queries\MatchableValues;
use App\Modules\Taxonomy\Data\OccupationListItem;
use App\Modules\Taxonomy\Queries\PublicOccupations;
use App\Support\Localization\LocalizedRoutes;
use Illuminate\Support\Facades\Config;

/**
 * The guided filter page (PLAN M20): the questions, the person's answers (from the URL, never
 * stored) and the occupations that match, each with why it matches, the status of the data behind
 * each reason, and its limits. The wording is always "matches your situation" (rule 11).
 */
final class GuidePresenter
{
    public function __construct(
        private readonly MatchOccupations $match,
        private readonly MatchableValues $values,
        private readonly PublicOccupations $occupations,
        private readonly OccupationLinks $links,
        private readonly FieldDisplay $display,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function present(GuideAnswers $answers, string $locale): array
    {
        $definitions = $this->values->definitions(GuideQuestions::fields());

        return [
            'questions' => array_map(fn (string $key, array $question): array => [
                'key' => $key,
                'title' => __("guide.questions.{$key}.title"),
                'help' => __("guide.questions.{$key}.help"),
                'options' => array_map(fn (string $option): array => [
                    'key' => $option,
                    'label' => __("guide.questions.{$key}.options.{$option}"),
                ], $question['options']),
            ], array_keys(GuideQuestions::QUESTIONS), GuideQuestions::QUESTIONS),
            'answers' => (object) $answers->answers,
            'results' => $answers->isEmpty() ? null : $this->results($answers, $definitions, $locale),
            'guideUrl' => LocalizedRoutes::path('guide.index', [], $locale),
            'compareUrl' => LocalizedRoutes::path('compare.index', [], $locale),
        ];
    }

    /**
     * @param  array<string, FieldDefinitionData>  $definitions
     * @return array<string, mixed>
     */
    private function results(GuideAnswers $answers, array $definitions, string $locale): array
    {
        $result = $this->match->handle($answers, Config::integer('atlas.discovery.max_matches'));
        $occupations = [];

        foreach ($this->occupations->findMany(array_map(fn (OccupationMatch $match): int => $match->occupationId, $result->matches)) as $item) {
            $occupations[$item->id] = $item;
        }

        $matches = [];

        foreach ($result->matches as $match) {
            $occupation = $occupations[$match->occupationId] ?? null;

            if ($occupation instanceof OccupationListItem) {
                $matches[] = $this->match($match, $occupation, $definitions, $locale);
            }
        }

        return [
            'total' => $result->total,
            'checked' => $result->checked,
            'conditions' => count($answers->conditions()),
            'matches' => $matches,
            'limits' => [__('guide.limits.not_a_promise'), __('guide.limits.basic_facts_only'), __('guide.limits.drafts')],
        ];
    }

    /**
     * @param  array<string, FieldDefinitionData>  $definitions
     * @return array<string, mixed>
     */
    private function match(OccupationMatch $match, OccupationListItem $occupation, array $definitions, string $locale): array
    {
        $reasons = [];
        $limits = [];

        foreach ($match->criteria as $criterion) {
            $definition = $definitions[$criterion->field] ?? null;
            $label = $definition === null ? $criterion->field : ($this->display->text($definition->label, $locale) ?? $criterion->field);

            if ($criterion->outcome === MatchOutcome::Unknown) {
                $limits[] = __('guide.limits.unknown', ['field' => $label]);

                continue;
            }

            if ($criterion->outcome === MatchOutcome::Partly) {
                $limits[] = __("guide.limits.partly.{$criterion->question}");
            }

            $reasons[] = $this->reason($criterion, $definition, $label, $locale);
        }

        foreach ($match->notes as $note) {
            $limits[] = __("guide.limits.notes.{$note}");
        }

        $unverified = array_filter($reasons, fn (array $reason): bool => $reason['status'] !== FieldStatus::Verified->value);

        if ($unverified !== []) {
            $limits[] = __('guide.limits.unverified_reasons');
        }

        return [
            'occupation' => $this->links->link($occupation, $locale),
            'reasons' => $reasons,
            'limits' => $limits,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function reason(Criterion $criterion, ?FieldDefinitionData $definition, string $label, string $locale): array
    {
        return [
            'question' => $criterion->question,
            'answer' => __("guide.questions.{$criterion->question}.options.{$criterion->answer}"),
            'field' => $label,
            'value' => $definition === null ? null : $this->display->display($definition, $criterion->value, $locale),
            'status' => $criterion->status?->value,
            'outcome' => $criterion->outcome->value,
        ];
    }
}
