<?php

namespace App\Support\Localization;

/**
 * Compares the translation files of every locale under lang/. Each locale must have the same keys,
 * each line must be a non-empty string, and a line must use the same `:placeholders` in every
 * locale. Works without booting Laravel, so `scripts/ci/check-translations` can run it on its own.
 */
final readonly class TranslationParity
{
    public function __construct(private TranslationFiles $files) {}

    /**
     * Every problem found, one readable line each; empty when the locales agree.
     *
     * @return list<string>
     */
    public function problems(): array
    {
        $locales = $this->files->locales();

        if (count($locales) < 2) {
            return ['Expected at least two locales under lang/, found: '.(implode(', ', $locales) ?: 'none').'.'];
        }

        $strings = [];

        foreach ($locales as $locale) {
            $strings[$locale] = $this->files->strings($locale);
        }

        $keys = array_unique(array_merge(...array_map(array_keys(...), array_values($strings))));
        sort($keys);

        $problems = [];

        foreach ($keys as $key) {
            $placeholders = [];

            foreach ($locales as $locale) {
                if (! array_key_exists($key, $strings[$locale])) {
                    $problems[] = "[{$locale}] missing: {$key}";

                    continue;
                }

                $line = $strings[$locale][$key];

                if (! is_string($line) || trim($line) === '') {
                    $problems[] = "[{$locale}] empty or not text: {$key}";

                    continue;
                }

                $placeholders[$locale] = self::placeholders($line);
            }

            if (count(array_unique(array_map(fn (array $names): string => implode(',', $names), $placeholders))) > 1) {
                $found = implode('; ', array_map(
                    fn (string $locale, array $names): string => "{$locale}: ".(implode(' ', $names) ?: '(none)'),
                    array_keys($placeholders),
                    $placeholders,
                ));
                $problems[] = "placeholders differ: {$key} ({$found})";
            }
        }

        return $problems;
    }

    /**
     * The `:name` placeholders of a line, lowercased (`:Attribute` is the same placeholder as
     * `:attribute`), sorted and without duplicates.
     *
     * @return list<string>
     */
    public static function placeholders(string $line): array
    {
        preg_match_all('/:([a-z_]+)/i', $line, $matches);

        $names = array_values(array_unique(array_map(
            fn (string $name): string => ':'.strtolower($name),
            $matches[1],
        )));
        sort($names);

        return $names;
    }
}
