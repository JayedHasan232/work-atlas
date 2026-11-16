<?php

namespace App\Support\Localization;

use RuntimeException;

/**
 * Reads lang/ directly from disk, without booting Laravel, so the CI parity check
 * (scripts/ci/check-translations) and the TypeScript key generator share one reader.
 */
final class TranslationFiles
{
    public function __construct(private readonly string $langPath) {}

    /**
     * The locales that have a directory under lang/.
     *
     * @return list<string>
     */
    public function locales(): array
    {
        $directories = glob($this->langPath.'/*', GLOB_ONLYDIR) ?: [];
        $locales = array_map(basename(...), $directories);
        sort($locales);

        return $locales;
    }

    /**
     * Every string of a locale, flattened to dot keys: `group.key.sub` for lang/<locale>/<group>.php
     * and `json:<text>` for lang/<locale>.json.
     *
     * @return array<string, mixed>
     */
    public function strings(string $locale): array
    {
        $strings = [];

        foreach ($this->groups($locale) as $group) {
            $strings += $this->flatten($this->group($locale, $group), $group.'.');
        }

        $json = $this->langPath."/{$locale}.json";

        if (is_file($json)) {
            $decoded = json_decode((string) file_get_contents($json), true, flags: JSON_THROW_ON_ERROR);

            if (! is_array($decoded)) {
                throw new RuntimeException("{$json} must contain a JSON object.");
            }

            foreach ($decoded as $key => $value) {
                $strings["json:{$key}"] = $value;
            }
        }

        ksort($strings);

        return $strings;
    }

    /**
     * The group names (file names without .php) of a locale.
     *
     * @return list<string>
     */
    public function groups(string $locale): array
    {
        $files = glob($this->langPath."/{$locale}/*.php") ?: [];
        $groups = array_map(fn (string $file): string => basename($file, '.php'), $files);
        sort($groups);

        return $groups;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function group(string $locale, string $group): array
    {
        $file = $this->langPath."/{$locale}/{$group}.php";
        $lines = is_file($file) ? require $file : [];

        if (! is_array($lines)) {
            throw new RuntimeException("{$file} must return an array.");
        }

        return $lines;
    }

    /**
     * @param  array<array-key, mixed>  $lines
     * @return array<string, mixed>
     */
    private function flatten(array $lines, string $prefix): array
    {
        $flat = [];

        foreach ($lines as $key => $value) {
            if (is_array($value)) {
                $flat += $this->flatten($value, "{$prefix}{$key}.");
            } else {
                $flat["{$prefix}{$key}"] = $value;
            }
        }

        return $flat;
    }
}
