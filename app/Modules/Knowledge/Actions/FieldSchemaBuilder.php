<?php

namespace App\Modules\Knowledge\Actions;

use App\Modules\Knowledge\Data\FieldDefinitionData;
use App\Modules\Knowledge\Enums\FieldType;
use InvalidArgumentException;

/**
 * The JSON schema (draft 2020-12) for one wave's AI output: `{"fields": {"<key>": {"value": …,
 * "confidence": 0–1, "note": "…"}}}` with every field of the wave. A value is a valid value of its
 * field or the string "unknown"; the confidence is required, the note optional. Nothing else is allowed.
 */
final class FieldSchemaBuilder
{
    public const string UNKNOWN = 'unknown';

    public const int NOTE_MAX_LENGTH = 2000;

    /**
     * @param  list<FieldDefinitionData>  $definitions  one per key, all of the same wave
     * @return array<string, mixed>
     */
    public function handle(array $definitions, int $schemaVersion): array
    {
        if ($definitions === []) {
            throw new InvalidArgumentException('A schema needs at least one field.');
        }

        $wave = $definitions[0]->wave;
        $properties = [];

        foreach ($definitions as $definition) {
            if ($definition->wave !== $wave || isset($properties[$definition->key])) {
                throw new InvalidArgumentException('A schema takes one definition per key, all of one wave.');
            }

            $properties[$definition->key] = $this->answer($definition);
        }

        return [
            '$schema' => 'https://json-schema.org/draft/2020-12/schema',
            '$id' => "urn:work-atlas:schema:wave{$wave}:fields:v{$schemaVersion}",
            'title' => "Wave {$wave} fields, v{$schemaVersion}",
            'description' => 'Generated from data/fields by atlas:fields:generate. Do not edit; a change is a new version.',
            'type' => 'object',
            'required' => ['fields'],
            'additionalProperties' => false,
            'properties' => [
                'fields' => [
                    'type' => 'object',
                    'required' => array_keys($properties),
                    'additionalProperties' => false,
                    'properties' => $properties,
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function answer(FieldDefinitionData $definition): array
    {
        return [
            'description' => "{$definition->key} v{$definition->version}: {$definition->help['en']}",
            'type' => 'object',
            'required' => ['value', 'confidence'],
            'additionalProperties' => false,
            'properties' => [
                'value' => ['anyOf' => [$this->value($definition), ['const' => self::UNKNOWN]]],
                'confidence' => ['type' => 'number', 'minimum' => 0, 'maximum' => 1],
                'note' => ['type' => 'string', 'maxLength' => self::NOTE_MAX_LENGTH],
            ],
        ];
    }

    /**
     * Mirrors ValidateFieldValue, which checks the value again when it is recorded.
     *
     * @return array<string, mixed>
     */
    private function value(FieldDefinitionData $definition): array
    {
        return match ($definition->type) {
            FieldType::Scale1To5 => ['type' => 'integer', 'minimum' => 1, 'maximum' => 5],
            FieldType::Enum, FieldType::Band => ['type' => 'string', 'enum' => $definition->options],
            FieldType::MultiEnum => [
                'type' => 'array',
                'minItems' => 1,
                'uniqueItems' => true,
                'items' => ['type' => 'string', 'enum' => $definition->options],
            ],
            FieldType::Boolean => ['type' => 'boolean'],
            FieldType::Text => ['type' => 'string', 'minLength' => 1, 'maxLength' => ValidateFieldValue::TEXT_MAX_LENGTH],
            FieldType::NameList => [
                'type' => 'array',
                'minItems' => 1,
                'maxItems' => ValidateFieldValue::NAMES_MAX_COUNT,
                'contains' => ['type' => 'object', 'properties' => ['type' => ['const' => 'primary']], 'required' => ['type']],
                'minContains' => 1,
                'maxContains' => 1,
                'items' => [
                    'type' => 'object',
                    'required' => ['name', 'type'],
                    'additionalProperties' => false,
                    'properties' => [
                        'name' => ['type' => 'string', 'minLength' => 1, 'maxLength' => ValidateFieldValue::NAME_MAX_LENGTH],
                        'type' => ['type' => 'string', 'enum' => ValidateFieldValue::NAME_TYPES],
                        'region' => ['type' => 'string', 'pattern' => '^[a-z][a-z0-9_]*$', 'maxLength' => ValidateFieldValue::REGION_MAX_LENGTH],
                    ],
                ],
            ],
        };
    }
}
