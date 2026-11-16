<?php

namespace App\Modules\AiPipeline\Actions;

use App\Modules\AiPipeline\Data\AiRequest;
use App\Modules\AiPipeline\Exceptions\CrossCheckNeedsTwoModels;
use App\Modules\AiPipeline\Exceptions\InvalidPrompt;
use App\Modules\AiPipeline\PromptRegistry;
use App\Modules\Knowledge\Data\WaveField;
use App\Modules\Knowledge\Enums\FieldType;
use App\Modules\Taxonomy\Exceptions\UnknownOccupation;
use App\Modules\Taxonomy\Queries\OccupationContextById;

/**
 * The same Wave 1 prompt for one occupation, for model A and model B (PLAN M12). The prompt carries the
 * context (ISCO code and parents, every Bangla and English name) and each field's meaning, options
 * and anchors from the field definitions, so the prompt file itself never holds an anchor.
 */
final class BuildWave1Requests
{
    public const string PROMPT_KEY = 'wave1/fields';

    public const int PROMPT_VERSION = 1;

    public function __construct(
        private readonly OccupationContextById $context,
        private readonly PromptRegistry $prompts,
    ) {}

    /**
     * @param  list<WaveField>  $fields  the Wave 1 fields (Knowledge\Queries\WaveFields)
     * @return array{0: AiRequest, 1: AiRequest} model A, then model B
     */
    public function handle(int $occupationId, array $fields): array
    {
        $this->checkFields($fields);
        $context = $this->context->handle($occupationId) ?? throw UnknownOccupation::slugs(["#{$occupationId}"]);

        $values = [
            'isco_code' => $context->iscoCode,
            'hierarchy' => $context->hierarchy,
            'names_bn' => $context->namesIn('bn'),
            'names_en' => $context->namesIn('en'),
            'fields' => implode("\n\n", array_map($this->describe(...), $fields)),
        ];

        /** @var array{provider: string, model: string} $a */
        $a = config('atlas.ai.tasks.wave1.a');
        /** @var array{provider: string, model: string} $b */
        $b = config('atlas.ai.tasks.wave1.b');

        if ($a['provider'] === $b['provider'] && $a['model'] === $b['model']) {
            throw CrossCheckNeedsTwoModels::same($a['provider'], $a['model']);
        }

        return [
            new AiRequest(self::PROMPT_KEY, self::PROMPT_VERSION, $values, $a['provider'], $a['model']),
            new AiRequest(self::PROMPT_KEY, self::PROMPT_VERSION, $values, $b['provider'], $b['model']),
        ];
    }

    /**
     * The fields must be exactly the ones the prompt's schema asks for; a new field or version needs a
     * new prompt version.
     *
     * @param  list<WaveField>  $fields
     */
    private function checkFields(array $fields): void
    {
        $prompt = $this->prompts->get(self::PROMPT_KEY, self::PROMPT_VERSION);
        $schema = json_decode($prompt->schemaJson, true);
        $wanted = is_array($schema) ? ($schema['properties']['fields']['required'] ?? []) : [];
        $have = array_map(fn (WaveField $field): string => $field->key, $fields);
        sort($wanted);
        sort($have);

        if ($wanted !== $have) {
            throw InvalidPrompt::because($prompt->file, 'the schema asks for '.implode(', ', $wanted).'; the Wave 1 fields are '.(implode(', ', $have) ?: 'none').' (run atlas:fields:sync, or write a new prompt version)');
        }
    }

    private function describe(WaveField $field): string
    {
        $lines = [
            "### {$field->key}: ".($field->label['en'] ?? $field->key).' / '.($field->label['bn'] ?? ''),
            'Meaning: '.($field->help['en'] ?? '').' (Bangla: '.($field->help['bn'] ?? '').')',
        ];

        $lines[] = match ($field->type) {
            FieldType::Scale1To5 => 'Value: a whole number from 1 to 5, or "unknown".',
            FieldType::Enum => 'Value: one option key, or "unknown".',
            FieldType::MultiEnum => 'Value: one or more option keys as a list, or "unknown".',
            FieldType::Band => 'Value: one band key, or "unknown".',
            FieldType::Boolean => 'Value: true or false, or "unknown".',
            default => 'Value: as the schema says, or "unknown".',
        };

        foreach ($field->anchors['en'] ?? [] as $point => $anchor) {
            $lines[] = "- {$point}: {$anchor} (Bangla: ".($field->anchors['bn'][$point] ?? '').')';
        }

        foreach ($field->options as $option) {
            $labels = $field->optionLabels[$option] ?? [];
            $lines[] = "- {$option}: ".($labels['en'] ?? $option).' (Bangla: '.($labels['bn'] ?? '').')';
        }

        return implode("\n", $lines);
    }
}
