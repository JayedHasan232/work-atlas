<?php

namespace App\Support\Localization;

use Illuminate\Support\Facades\Config;

/**
 * The supported locales from config/atlas.php.
 */
final class Locales
{
    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_map(strval(...), array_keys(Config::array('atlas.locales')));
    }

    /**
     * @phpstan-assert-if-true string $locale
     */
    public static function isSupported(mixed $locale): bool
    {
        return is_string($locale) && in_array($locale, self::codes(), true);
    }

    public static function default(): string
    {
        return Config::string('app.locale');
    }

    public static function name(string $locale): string
    {
        return Config::string("atlas.locales.{$locale}.name");
    }

    /**
     * The URL prefix of a locale's public pages, or null for the locale that lives at the root.
     */
    public static function prefix(string $locale): ?string
    {
        $prefix = Config::get("atlas.locales.{$locale}.prefix");

        return is_string($prefix) && $prefix !== '' ? $prefix : null;
    }
}
