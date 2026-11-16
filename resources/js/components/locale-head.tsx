import { Head, usePage } from '@inertiajs/react';

/**
 * Canonical URL and hreflang alternates for public pages, which exist once per language. Bangla is
 * the default (`x-default`), since the site is Bangla first.
 */
export default function LocaleHead() {
    const { localization } = usePage().props;
    const alternates = localization.locales.filter(
        (option): option is typeof option & { url: string } =>
            option.url !== null,
    );
    const fallback = alternates.find((option) => option.code === 'bn');

    return (
        <Head>
            {localization.canonical !== null && (
                <link
                    head-key="canonical"
                    rel="canonical"
                    href={localization.canonical}
                />
            )}
            {alternates.map((option) => (
                <link
                    key={option.code}
                    head-key={`alternate-${option.code}`}
                    rel="alternate"
                    hrefLang={option.code}
                    href={option.url}
                />
            ))}
            {fallback !== undefined && (
                <link
                    head-key="alternate-x-default"
                    rel="alternate"
                    hrefLang="x-default"
                    href={fallback.url}
                />
            )}
        </Head>
    );
}
