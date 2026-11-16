import { usePage } from '@inertiajs/react';
import { formatDate, formatNumber } from '@/lib/format';
import type { Locale, Translations } from '@/types/localization';
import type { TranslationKey } from '@/types/translation-keys.generated';

export type Replacements = Record<string, string | number>;

/**
 * Looks up a dot key (`auth.login.title`) in the shared translation groups and fills in `:name`
 * placeholders. A missing key returns the key itself, so a gap is visible instead of blank.
 */
export function translate(
    translations: Translations,
    key: string,
    replacements: Replacements = {},
): string {
    let node: unknown = translations;

    for (const segment of key.split('.')) {
        node =
            typeof node === 'object' && node !== null
                ? (node as Record<string, unknown>)[segment]
                : undefined;
    }

    if (typeof node !== 'string') {
        return key;
    }

    // Longest names first, so `:count_total` is not cut short by `:count`.
    return Object.keys(replacements)
        .sort((a, b) => b.length - a.length)
        .reduce(
            (line, name) =>
                line.replaceAll(`:${name}`, String(replacements[name])),
            node,
        );
}

/**
 * Prefixes a path with the current locale's URL prefix (`/en` for English, nothing for Bangla), for
 * links between public pages, which exist once per locale.
 */
export function localizePath(path: string, prefix: string): string {
    if (prefix === '') {
        return path;
    }

    return path === '/' ? prefix : `${prefix}${path}`;
}

export function useTranslation() {
    const { translations, localization } = usePage().props;
    const locale: Locale = localization.locale;

    return {
        locale,
        t: (key: TranslationKey, replacements?: Replacements) =>
            translate(translations, key, replacements),
        formatNumber: (value: number, options?: Intl.NumberFormatOptions) =>
            formatNumber(value, locale, options),
        formatDate: (
            value: Date | string | number,
            options?: Intl.DateTimeFormatOptions,
        ) => formatDate(value, locale, options),
        localizePath: (path: string) => localizePath(path, localization.prefix),
    };
}
