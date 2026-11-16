<?php

namespace App\Modules\AiPipeline\Actions;

use App\Modules\AiPipeline\Data\AiResult;
use App\Modules\AiPipeline\Data\Wave1Result;
use App\Modules\AiPipeline\Enums\Wave1Status;
use App\Modules\Knowledge\Actions\RecordCrossCheck;
use App\Modules\Knowledge\Actions\RecordRevision;
use App\Modules\Knowledge\Data\Actor;
use App\Modules\Knowledge\Data\RevisionInput;
use App\Modules\Knowledge\Data\WaveField;
use App\Modules\Knowledge\Enums\CrossCheck;
use App\Modules\Knowledge\Exceptions\KnowledgeRuleViolation;
use Illuminate\Support\Facades\DB;

/**
 * Writes two models' Wave 1 answers for one occupation, all or nothing (PLAN M12, D50): model A's
 * value is the new current value (`ai_draft`, or a proposal when the value is verified), model B's is
 * a second opinion next to it, and the cross-check is saved on the slot.
 */
final class ApplyWave1
{
    public function __construct(
        private readonly RecordRevision $recordRevision,
        private readonly RecordCrossCheck $recordCrossCheck,
        private readonly AgreementCalculator $agreement,
    ) {}

    /**
     * @param  list<WaveField>  $fields
     */
    public function handle(int $occupationId, array $fields, AiResult $a, AiResult $b): Wave1Result
    {
        try {
            [$agreed, $disagreed] = DB::transaction(function () use ($occupationId, $fields, $a, $b): array {
                $agreed = 0;

                foreach ($fields as $field) {
                    $answerA = $this->answer($a, $field->key);
                    $answerB = $this->answer($b, $field->key);

                    $this->recordRevision->handle($this->input($occupationId, $field, $a->runId, $answerA, secondOpinion: false));
                    $this->recordRevision->handle($this->input($occupationId, $field, $b->runId, $answerB, secondOpinion: true));

                    $same = $this->agreement->handle($field->type, $answerA['value'] ?? null, $answerB['value'] ?? null, $field->options);
                    $this->recordCrossCheck->handle($occupationId, $field->id, $same ? CrossCheck::Agreed : CrossCheck::Disagreed);
                    $agreed += $same ? 1 : 0;
                }

                return [$agreed, count($fields) - $agreed];
            });
        } catch (KnowledgeRuleViolation $violation) {
            return new Wave1Result($occupationId, Wave1Status::Rejected, [$a->runId, $b->runId], error: $violation->getMessage());
        }

        return new Wave1Result($occupationId, Wave1Status::Generated, [$a->runId, $b->runId], $agreed, $disagreed);
    }

    /**
     * @return array<string, mixed>
     */
    private function answer(AiResult $result, string $key): array
    {
        $answer = $result->output['fields'][$key] ?? null;

        return is_array($answer) ? $answer : [];
    }

    /**
     * @param  array<string, mixed>  $answer
     */
    private function input(int $occupationId, WaveField $field, int $runId, array $answer, bool $secondOpinion): RevisionInput
    {
        $note = $answer['note'] ?? null;
        $confidence = $answer['confidence'] ?? null;

        return new RevisionInput(
            occupationId: $occupationId,
            fieldDefinitionId: $field->id,
            value: $answer['value'] ?? null,
            actor: Actor::aiRun($runId),
            confidence: is_int($confidence) || is_float($confidence) ? (float) $confidence : null,
            comment: is_string($note) && trim($note) !== '' ? trim($note) : null,
            secondOpinion: $secondOpinion,
        );
    }
}
