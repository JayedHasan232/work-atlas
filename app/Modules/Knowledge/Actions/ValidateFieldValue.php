<?php

namespace App\Modules\Knowledge\Actions;

use App\Modules\Knowledge\Enums\FieldType;
use App\Modules\Knowledge\Exceptions\InvalidFieldValue;
use App\Modules\Knowledge\Models\FieldDefinition;

/**
 * Checks a value against its field definition and returns it in stored form. `null` and "unknown" both
 * mean "not known" and are stored as null (rule 2: no source, no number). Text and name lists are per
 * language, so they need a locale; every other type is the same in every language (D22), so it must
 * not have one.
 *
 * A name list (D44) is an occupation's names in one language: exactly one primary name, then aliases,
 * local and regional names; a regional name carries its region. It is stored primary first, then in
 * the given order, as `[{name, type}, {name, type: regional, region}]`.
 */
final class ValidateFieldValue
{
    public const string UNKNOWN = 'unknown';

    public const int TEXT_MAX_LENGTH = 10000;

    public const int REGION_MAX_LENGTH = 32;

    public const int NAME_MAX_LENGTH = 200;

    public const int NAMES_MAX_COUNT = 50;

    /** The name types, as in Taxonomy's NameType (the projection into occupation_names). */
    public const array NAME_TYPES = ['primary', 'alias', 'local', 'regional'];

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
            FieldType::NameList => $this->names($definition, $value),
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

    /**
     * @return list<array{name: string, type: string, region?: string}>
     */
    private function names(FieldDefinition $definition, mixed $value): array
    {
        if (! is_array($value) || $value === [] || ! array_is_list($value) || count($value) > self::NAMES_MAX_COUNT) {
            $this->fail($definition, 'expected a list of 1 to '.self::NAMES_MAX_COUNT.' names');
        }

        $names = [];
        $seen = [];

        foreach ($value as $item) {
            if (! is_array($item) || array_diff(array_keys($item), ['name', 'type', 'region']) !== []) {
                $this->fail($definition, 'each name is {name, type} or {name, type: regional, region}');
            }

            $name = is_string($item['name'] ?? null) ? trim((string) preg_replace('/\s+/u', ' ', $item['name'])) : '';
            $type = $item['type'] ?? null;
            $region = $item['region'] ?? null;

            if ($name === '' || mb_strlen($name) > self::NAME_MAX_LENGTH) {
                $this->fail($definition, 'a name needs 1 to '.self::NAME_MAX_LENGTH.' characters');
            }

            if (! in_array($type, self::NAME_TYPES, true)) {
                $this->fail($definition, 'a name type is one of '.implode(', ', self::NAME_TYPES));
            }

            if (($type === 'regional') !== ($region !== null)) {
                $this->fail($definition, 'a regional name needs a region, and only a regional name has one');
            }

            if ($region !== null && (! is_string($region) || preg_match('/^[a-z][a-z0-9_]*$/', $region) !== 1 || strlen($region) > self::REGION_MAX_LENGTH)) {
                $this->fail($definition, 'a region is a short lower-case key');
            }

            $key = mb_strtolower($name);

            if (isset($seen[$key])) {
                $this->fail($definition, "the name \"{$name}\" is listed twice");
            }

            $seen[$key] = true;
            $names[] = $region === null ? ['name' => $name, 'type' => $type] : ['name' => $name, 'type' => $type, 'region' => $region];
        }

        $primary = array_values(array_filter($names, fn (array $name): bool => $name['type'] === 'primary'));

        if (count($primary) !== 1) {
            $this->fail($definition, 'a name list has exactly one primary name');
        }

        return [...$primary, ...array_values(array_filter($names, fn (array $name): bool => $name['type'] !== 'primary'))];
    }

    private function checkLocale(FieldDefinition $definition, ?string $locale): void
    {
        if ($definition->type->isPerLanguage()) {
            /** @var array<string, mixed> $locales */
            $locales = config('atlas.locales');

            if ($locale === null || ! array_key_exists($locale, $locales)) {
                $this->fail($definition, "a {$definition->type->value} value needs a supported locale");
            }

            return;
        }

        if ($locale !== null) {
            $this->fail($definition, 'only text and name lists have a locale; other values are the same in every language');
        }
    }

    private function checkRegion(FieldDefinition $definition, ?string $region): void
    {
        if ($definition->type === FieldType::NameList && $region !== null) {
            $this->fail($definition, 'a name list is for all of Bangladesh; a regional name carries its own region');
        }

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
