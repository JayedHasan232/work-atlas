<?php

namespace App\Modules\Knowledge\Actions;

use App\Modules\Knowledge\Enums\FieldType;
use App\Modules\Knowledge\Exceptions\InvalidFieldValue;
use App\Modules\Knowledge\Models\FieldDefinition;

/**
 * Checks a value against its field definition and returns it in stored form. `null` and "unknown" both
 * mean "not known" and are stored as null (rule 2: no source, no number). Text is per language, so it
 * needs a locale; every other type is the same in every language (D22), so it must not have one.
 */
final class ValidateFieldValue
{
    public const string UNKNOWN = 'unknown';

    public const int TEXT_MAX_LENGTH = 10000;

    public const int REGION_MAX_LENGTH = 32;

    public function handle(FieldDefinition $definition, mixed $value, ?string $locale = null, ?string $region = null): mixed
    {
        $this->checkLocale($definition, $locale);
        $this->checkRegion($definition, $region);

        if ($value === null || $value === self::UNKNOWN) {
            return null;
        }

        return match ($definition->type) {
            FieldType::Scale1To5 => $this->scale($definition, $value),
            FieldType::Enum, FieldType::Band => $this->option($definition, $value),
            FieldType::MultiEnum => $this->options($definition, $value),
            FieldType::Boolean => is_bool($value) ? $value : $this->fail($definition, 'expected true or false'),
            FieldType::Text => $this->text($definition, $value),
        };
    }

    private function scale(FieldDefinition $definition, mixed $value): int
    {
        if (! is_int($value) || $value < 1 || $value > 5) {
            $this->fail($definition, 'expected a whole number from 1 to 5');
        }

        return $value;
    }

    private function option(FieldDefinition $definition, mixed $value): string
    {
        if (! is_string($value) || ! in_array($value, $this->allowedOptions($definition), true)) {
            $this->fail($definition, 'expected one of the defined options');
        }

        return $value;
    }

    /**
     * @return list<string> the chosen options, distinct, in the definition's order
     */
    private function options(FieldDefinition $definition, mixed $value): array
    {
        $allowed = $this->allowedOptions($definition);

        if (! is_array($value) || $value === [] || ! array_is_list($value)) {
            $this->fail($definition, 'expected a non-empty list of options');
        }

        foreach ($value as $option) {
            if (! is_string($option) || ! in_array($option, $allowed, true)) {
                $this->fail($definition, 'expected only defined options');
            }
        }

        if (count(array_unique($value)) !== count($value)) {
            $this->fail($definition, 'an option is listed twice');
        }

        return array_values(array_intersect($allowed, $value));
    }

    private function text(FieldDefinition $definition, mixed $value): string
    {
        if (! is_string($value)) {
            $this->fail($definition, 'expected text');
        }

        $text = trim($value);

        if ($text === '') {
            $this->fail($definition, 'text is empty; use null for an unknown value');
        }

        if (mb_strlen($text) > self::TEXT_MAX_LENGTH) {
            $this->fail($definition, 'text is longer than '.self::TEXT_MAX_LENGTH.' characters');
        }

        return $text;
    }

    private function checkLocale(FieldDefinition $definition, ?string $locale): void
    {
        if ($definition->type === FieldType::Text) {
            /** @var array<string, mixed> $locales */
            $locales = config('atlas.locales');

            if ($locale === null || ! array_key_exists($locale, $locales)) {
                $this->fail($definition, 'text needs a supported locale');
            }

            return;
        }

        if ($locale !== null) {
            $this->fail($definition, 'only text has a locale; other values are the same in every language');
        }
    }

    private function checkRegion(FieldDefinition $definition, ?string $region): void
    {
        if ($region !== null && (trim($region) === '' || mb_strlen($region) > self::REGION_MAX_LENGTH)) {
            $this->fail($definition, 'region must be null (all of Bangladesh) or a short region key');
        }
    }

    /**
     * @return list<string>
     */
    private function allowedOptions(FieldDefinition $definition): array
    {
        return array_values(array_filter($definition->options ?? [], 'is_string'));
    }

    private function fail(FieldDefinition $definition, string $reason): never
    {
        throw InvalidFieldValue::because($definition->key, $reason);
    }
}
