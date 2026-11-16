<?php

namespace App\Modules\Taxonomy\Actions;

use App\Modules\Knowledge\Actions\RecordRevision;
use App\Modules\Knowledge\Data\Actor;
use App\Modules\Knowledge\Data\RevisionInput;
use App\Modules\Knowledge\Exceptions\KnowledgeRuleViolation;
use App\Modules\Knowledge\Queries\FieldDefinitionByKey;
use App\Modules\Taxonomy\Data\ExpansionResult;
use App\Modules\Taxonomy\Enums\ExpansionStatus;
use App\Modules\Taxonomy\Exceptions\NamesFieldMissing;
use App\Modules\Taxonomy\Exceptions\UnknownUnitGroup;
use Illuminate\Support\Facades\DB;

/**
 * Writes one schema-valid expansion output (data/schemas/taxonomy/expand-unit-group.v1.json): each
 * proposed occupation becomes a `candidate` linked to the run, and its Bangla and English names go
 * through Knowledge as `ai_draft` values of the `names` field (D44); specializations are added as
 * aliases. All in one transaction: if any value breaks a Knowledge rule, nothing is written.
 *
 * The work arrangements, the reason for a separate occupation and the note stay in the run's parsed
 * output, for the list review (M11).
 */
final class ApplyExpansion
{
    public function __construct(
        private readonly RecordRevision $recordRevision,
        private readonly CreateCandidateOccupation $createCandidate,
        private readonly FieldDefinitionByKey $fieldDefinition,
        private readonly NormalizeName $normalize,
    ) {}

    /**
     * @param  array<string, mixed>  $output
     */
    public function handle(string $iscoCode, int $runId, array $output): ExpansionResult
    {
        $namesField = $this->fieldDefinition->handle(SyncOccupationNames::FIELD_KEY) ?? throw NamesFieldMissing::make();

        /** @var list<array<string, mixed>> $occupations */
        $occupations = is_array($output['occupations'] ?? null) ? array_values($output['occupations']) : [];

        try {
            $ids = DB::transaction(fn (): array => array_map(
                fn (array $item): int => $this->write($iscoCode, $runId, $namesField, $item),
                $occupations,
            ));
        } catch (KnowledgeRuleViolation|UnknownUnitGroup $exception) {
            return new ExpansionResult($iscoCode, ExpansionStatus::Rejected, $runId, error: $exception->getMessage());
        }

        return new ExpansionResult($iscoCode, ExpansionStatus::Expanded, $runId, $ids);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function write(string $iscoCode, int $runId, int $namesField, array $item): int
    {
        $specializations = is_array($item['specializations'] ?? null) ? $item['specializations'] : [];
        $names = [
            'bn' => $this->withAliases($this->list($item['names_bn'] ?? null), array_column($specializations, 'bn')),
            'en' => $this->withAliases($this->list($item['names_en'] ?? null), array_column($specializations, 'en')),
        ];
        $primary = array_values(array_filter($names['en'], fn (array $name): bool => ($name['type'] ?? null) === 'primary'));

        $occupationId = $this->createCandidate->handle($iscoCode, (string) ($primary[0]['name'] ?? ''), $runId);

        foreach ($names as $locale => $list) {
            $this->recordRevision->handle(new RevisionInput(
                occupationId: $occupationId,
                fieldDefinitionId: $namesField,
                value: $list,
                actor: Actor::aiRun($runId),
                locale: $locale,
                confidence: (float) ($item['confidence'] ?? 0),
                comment: "AI list expansion of ISCO-08 unit group {$iscoCode}",
            ));
        }

        return $occupationId;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function list(mixed $names): array
    {
        return is_array($names) ? array_values(array_filter($names, is_array(...))) : [];
    }

    /**
     * Adds each specialization as an alias, unless the list already has that name.
     *
     * @param  list<array<string, mixed>>  $names
     * @param  array<int, mixed>  $specializations
     * @return list<array<string, mixed>>
     */
    private function withAliases(array $names, array $specializations): array
    {
        $seen = array_map(fn (array $name): string => $this->normalize->handle((string) ($name['name'] ?? '')), $names);

        foreach ($specializations as $specialization) {
            $key = is_string($specialization) ? $this->normalize->handle($specialization) : '';

            if ($key !== '' && ! in_array($key, $seen, true)) {
                $names[] = ['name' => $specialization, 'type' => 'alias'];
                $seen[] = $key;
            }
        }

        return $names;
    }
}
