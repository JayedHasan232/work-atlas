import { Head, Link } from '@inertiajs/react';
import SearchBox from '@/components/search-box';
import { useTranslation } from '@/lib/i18n';
import type { Locale } from '@/types/localization';
import type { OccupationLink } from '@/types/occupations';

type SearchResult = OccupationLink & {
    /** The name that matched, when it is not the one shown (an alias or a local name). */
    matchedName: string | null;
    matchedLocale: Locale;
    match: 'exact' | 'prefix' | 'similar';
};

type Props = {
    query: string;
    results: SearchResult[];
};

// Search by any name of an occupation, in Bangla or English (PLAN M15).
export default function SearchPage({ query, results }: Props) {
    const { t, formatNumber, localizePath } = useTranslation();
    const title =
        query === '' ? t('search.title') : t('search.results_title', { query });

    return (
        <>
            <Head title={title} />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 px-4 py-8">
                <header className="flex flex-col gap-3">
                    <h1 className="text-3xl font-semibold">{title}</h1>
                    <p>{t('search.intro')}</p>
                    <SearchBox defaultValue={query} autoFocus={query === ''} />
                </header>

                {query !== '' && (
                    <section
                        aria-labelledby="results"
                        className="flex flex-col gap-3"
                    >
                        <h2 id="results" className="text-xl font-semibold">
                            {t('search.count', {
                                count: formatNumber(results.length),
                            })}
                        </h2>

                        {results.length === 0 ? (
                            <div className="flex flex-col gap-2">
                                <p>{t('search.empty', { query })}</p>
                                <p className="text-muted-foreground">
                                    {t('search.empty_hint')}
                                </p>
                            </div>
                        ) : (
                            <ul className="flex flex-col divide-y rounded-md border">
                                {results.map((result) => (
                                    <li key={result.slug}>
                                        <Link
                                            href={result.url}
                                            className="flex flex-col gap-0.5 p-3 hover:bg-accent"
                                        >
                                            <span lang={result.nameLocale}>
                                                {result.name}
                                            </span>
                                            {result.matchedName !== null && (
                                                <span className="text-sm text-muted-foreground">
                                                    {t('search.also_called', {
                                                        name: result.matchedName,
                                                    })}
                                                    {result.match ===
                                                        'similar' &&
                                                        ` (${t('search.similar')})`}
                                                </span>
                                            )}
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>
                )}

                <p className="flex flex-col gap-1 text-sm text-muted-foreground">
                    <Link
                        href={localizePath('/occupations')}
                        className="underline underline-offset-4 hover:text-foreground"
                    >
                        {t('search.browse')}
                    </Link>
                    <span>{t('search.missing')}</span>
                </p>
            </div>
        </>
    );
}
