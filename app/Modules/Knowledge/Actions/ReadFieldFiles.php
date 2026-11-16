<?php

namespace App\Modules\Knowledge\Actions;

use App\Modules\Knowledge\Data\FieldDefinitionData;
use App\Modules\Knowledge\Enums\FieldType;
use App\Modules\Knowledge\Exceptions\InvalidFieldFile;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Reads and checks the field definition files (`data/fields/wave<N>.v<M>.yaml`). Every field needs a
 * label and a help text in every supported locale; a choice field (enum, multi-enum, band) needs at
 * least two options, each labelled in every locale; a 1–5 scale needs five anchors in every locale.
 * Other fields have neither. A key and version appear once across all files.
 */
final class ReadFieldFiles
{
    private const string KEY_PATTERN = '/^[a-z][a-z0-9_]{0,63}$/';

    private const string FILE_PATTERN = '/^wave([1-6])\.v([1-9]\d*)\.yaml$/';

    private const array FIELD_KEYS = ['key', 'version', 'group', 'type', 'label', 'help', 'options', 'anchors', 'sensitive', 'requires_source'];

    private const array SCALE_POINTS = [1, 2, 3, 4, 5];

    /**
     * @return list<FieldDefinitionData> in file order (wave, then file version, then position)
     */
    public function handle(string $directory): array
    {
        $files = glob(rtrim($directory, '/').'/*.yaml') ?: [];

        if ($files === []) {
            throw InvalidFieldFile::because($directory, 'no wave<N>.v<M>.yaml files found');
        }

        usort($files, fn (string $a, string $b): int => $this->fileOrder($a) <=> $this->fileOrder($b));
        $definitions = [];
        $seen = [];

        foreach ($files as $file) {
            foreach ($this->readFile($file) as $definition) {
                $id = "{$definition->key} v{$definition->version}";

                if (isset($seen[$id])) {
                    throw InvalidFieldFile::because($file, "{$id} is already defined in {$seen[$id]}");
                }

                $seen[$id] = basename($file);
                $definitions[] = $definition;
            }
        }

        return $definitions;
    }

    /**
     * @return array{int, int}
     */
    private function fileOrder(string $file): array
    {
        if (preg_match(self::FILE_PATTERN, basename($file), $match) !== 1) {
            throw InvalidFieldFile::because($file, 'the name must be wave<N>.v<M>.yaml');
        }

        return [(int) $match[1], (int) $match[2]];
    }

    /**
     * @return list<FieldDefinitionData>
     */
    private function readFile(string $file): array
    {
        [$wave] = $this->fileOrder($file);

        try {
            $data = Yaml::parseFile($file);
        } catch (ParseException $exception) {
            throw InvalidFieldFile::because($file, $exception->getMessage());
        }

        if (! is_array($data) || array_keys($data) !== ['wave', 'fields'] || $data['wave'] !== $wave) {
            throw InvalidFieldFile::because($file, "the file must hold `wave: {$wave}` and a `fields` list");
        }

        if (! is_array($data['fields']) || ! array_is_list($data['fields']) || $data['fields'] === []) {
            throw InvalidFieldFile::because($file, '`fields` must be a non-empty list');
        }

        return array_map(
            fn (mixed $field, int $index): FieldDefinitionData => $this->field($file, $wave, $field, $index + 1),
            $data['fields'],
            array_keys($data['fields']),
        );
    }

    private function field(string $file, int $wave, mixed $field, int $position): FieldDefinitionData
    {
        $where = "field {$position}";

        if (! is_array($field)) {
            throw InvalidFieldFile::because($file, "{$where} must be a mapping");
        }

        if (is_string($field['key'] ?? null)) {
            $where = "field {$field['key']}";
        }

        if (($unknown = array_diff(array_keys($field), self::FIELD_KEYS)) !== []) {
            throw InvalidFieldFile::because($file, "{$where} has unknown keys: ".implode(', ', $unknown));
        }

        $key = $field['key'] ?? null;
        $version = $field['version'] ?? null;
        $group = $field['group'] ?? null;
        $type = is_string($field['type'] ?? null) ? FieldType::tryFrom($field['type']) : null;

        if (! is_string($key) || preg_match(self::KEY_PATTERN, $key) !== 1) {
            throw InvalidFieldFile::because($file, "{$where} needs a key of lower-case letters, digits and _");
        }

        if (! is_int($version) || $version < 1) {
            throw InvalidFieldFile::because($file, "{$where} needs a version of 1 or more");
        }

        if (! is_string($group) || preg_match(self::KEY_PATTERN, $group) !== 1) {
            throw InvalidFieldFile::because($file, "{$where} needs a group of lower-case letters, digits and _");
        }

        if ($type === null) {
            throw InvalidFieldFile::because($file, "{$where} needs a type: ".implode(', ', array_column(FieldType::cases(), 'value')));
        }

        foreach (['sensitive', 'requires_source'] as $flag) {
            if (array_key_exists($flag, $field) && ! is_bool($field[$flag])) {
                throw InvalidFieldFile::because($file, "{$where}: {$flag} must be true or false");
            }
        }

        [$options, $optionLabels] = $this->options($file, $where, $type, $field['options'] ?? null);

        return new FieldDefinitionData(
            key: $key,
            version: $version,
            wave: $wave,
            group: $group,
            type: $type,
            label: $this->localized($file, "{$where} label", $field['label'] ?? null),
            help: $this->localized($file, "{$where} help", $field['help'] ?? null),
            options: $options,
            optionLabels: $optionLabels,
            anchors: $this->anchors($file, $where, $type, $field['anchors'] ?? null),
            sensitive: $field['sensitive'] ?? false,
            requiresSource: $field['requires_source'] ?? false,
            file: basename($file),
        );
    }

    /**
     * @return array{list<string>|null, array<string, array<string, string>>|null}
     */
    private function options(string $file, string $where, FieldType $type, mixed $options): array
    {
        $isChoice = in_array($type, [FieldType::Enum, FieldType::MultiEnum, FieldType::Band], true);

        if (! $isChoice) {
            if ($options !== null) {
                throw InvalidFieldFile::because($file, "{$where}: a {$type->value} field has no options");
            }

            return [null, null];
        }

        if (! is_array($options) || ! array_is_list($options) || count($options) < 2) {
            throw InvalidFieldFile::because($file, "{$where} needs a list of at least two options");
        }

        $keys = [];
        $labels = [];

        foreach ($options as $option) {
            $key = is_array($option) ? ($option['key'] ?? null) : null;

            if (! is_string($key) || preg_match(self::KEY_PATTERN, $key) !== 1) {
                throw InvalidFieldFile::because($file, "{$where}: every option needs a key of lower-case letters, digits and _ (quote yes and no)");
            }

            if (isset($labels[$key])) {
                throw InvalidFieldFile::because($file, "{$where}: option {$key} is listed twice");
            }

            unset($option['key']);
            $keys[] = $key;
            $labels[$key] = $this->localized($file, "{$where} option {$key}", $option);
        }

        return [$keys, $labels];
    }

    /**
     * @return array<string, array<int, string>>|null
     */
    private function anchors(string $file, string $where, FieldType $type, mixed $anchors): ?array
    {
        if ($type !== FieldType::Scale1To5) {
            if ($anchors !== null) {
                throw InvalidFieldFile::because($file, "{$where}: only a 1–5 scale has anchors");
            }

            return null;
        }

        if (! is_array($anchors) || array_keys($anchors) !== self::SCALE_POINTS) {
            throw InvalidFieldFile::because($file, "{$where} needs anchors for 1, 2, 3, 4 and 5, in that order");
        }

        $byLocale = [];

        foreach ($anchors as $point => $anchor) {
            foreach ($this->localized($file, "{$where} anchor {$point}", $anchor) as $locale => $text) {
                $byLocale[$locale][$point] = $text;
            }
        }

        return $byLocale;
    }

    /**
     * @return array<string, string> by locale, in the order of config('atlas.locales')
     */
    private function localized(string $file, string $where, mixed $value): array
    {
        /** @var array<string, mixed> $supported */
        $supported = config('atlas.locales');
        $locales = array_keys($supported);

        if (! is_array($value) || array_diff(array_keys($value), $locales) !== [] || array_diff($locales, array_keys($value)) !== []) {
            throw InvalidFieldFile::because($file, "{$where} needs text in exactly these locales: ".implode(', ', $locales));
        }

        $texts = [];

        foreach ($locales as $locale) {
            $text = $value[$locale];

            if (! is_string($text) || trim($text) === '') {
                throw InvalidFieldFile::because($file, "{$where} has no {$locale} text");
            }

            $texts[$locale] = trim($text);
        }

        return $texts;
    }
}
