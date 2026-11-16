<?php

namespace App\Modules\Publishing\Presenters;

use App\Modules\Community\Queries\NextVerificationTask;
use App\Modules\Taxonomy\Queries\PublicOccupations;
use App\Support\Localization\LocalizedRoutes;
use Illuminate\Support\Facades\Config;

/**
 * The micro-task page (PLAN M17): the next yes/no question for a person, or none left. Only values
 * that are not sensitive are ever asked about (Knowledge's VerifiableValues), so the sensitive gate
 * holds here by construction.
 */
final class MicroTaskPresenter
{
    public function __construct(
        private readonly NextVerificationTask $next,
        private readonly PublicOccupations $occupations,
        private readonly OccupationLinks $links,
        private readonly FieldDisplay $display,
    ) {}

    /**
     * @return array{task: array<string, mixed>|null, answered: int, agreementsNeeded: int}
     */
    public function present(int $userId, string $locale): array
    {
        $answered = $this->next->answeredBy($userId);
        $agreementsNeeded = Config::integer('atlas.community.consensus_agreements');
        $task = $this->next->handle($userId);
        $occupation = $task === null ? null : $this->occupations->find($task->value->occupationId);

        if ($task === null || $occupation === null) {
            return ['task' => null, 'answered' => $answered, 'agreementsNeeded' => $agreementsNeeded];
        }

        $definition = $task->value->definition;

        return [
            'task' => [
                'id' => $task->taskId,
                'occupation' => $this->links->link($occupation, $locale),
                'field' => [
                    'key' => $definition->key,
                    'label' => $this->display->text($definition->label, $locale) ?? $definition->key,
                    'help' => $this->display->text($definition->help, $locale),
                ],
                'value' => $this->display->display($definition, $task->value->value, $locale),
                'status' => $task->value->status->value,
                'answerUrl' => LocalizedRoutes::path('tasks.answer', ['task' => $task->taskId], $locale),
                'suggestUrl' => LocalizedRoutes::path('occupations.suggest', ['slug' => $occupation->slug, 'field' => $definition->key], $locale),
            ],
            'answered' => $answered,
            'agreementsNeeded' => $agreementsNeeded,
        ];
    }
}
