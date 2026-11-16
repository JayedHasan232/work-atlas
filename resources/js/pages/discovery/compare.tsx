import { Head, Link, router } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import ValueText from '@/components/discovery/value-text';
import StatusBadge from '@/components/occupations/status-badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/lib/i18n';
import type { Compare as CompareProps } from '@/types/discovery';

// Two or three occupations side by side (PLAN M20). Every value shows its status.
export default function Compare({ compare }: { compare: CompareProps }) {
    const { t } = useTranslation();
    const [query, setQuery] = useState(compare.query ?? '');
    const full = compare.occupations.length >= compare.max;

    const search = (event: FormEvent) => {
        event.preventDefault();
        router.get(compare.searchUrl, { q: query }, { preserveScroll: true });
    };

    return (
        <>
            <Head title={t('guide.compare.title')} />
            <div className="mx-auto flex w-full max-w-5xl flex-col gap-6 px-4 py-8">
                <header className="flex flex-col gap-2">
                    <h1 className="text-2xl font-semibold">
                        {t('guide.compare.title')}
                    </h1>
                    <p className="text-muted-foreground">
                        {t('guide.compare.intro')}
                    </p>
                </header>

                {compare.occupations.length === 0 ? (
                    <p className="rounded-lg border p-5">
                        {t('guide.compare.empty')}
                    </p>
                ) : (
                    <div className="overflow-x-auto rounded-lg border">
                        <table
                            className="w-full min-w-[32rem] border-collapse text-sm"
                            data-test="compare-table"
                        >
                            <thead>
                                <tr className="border-b bg-muted/40">
                                    <th
                                        scope="col"
                                        className="p-3 text-start font-medium"
                                    >
                                        {t('guide.compare.field')}
                                    </th>
                                    {compare.occupations.map((occupation) => (
                                        <th
                                            key={occupation.slug}
                                            scope="col"
                                            className="p-3 text-start align-top"
                                        >
                                            <div className="flex flex-col gap-1">
                                                <Link
                                                    href={occupation.url}
                                                    lang={occupation.nameLocale}
                                                    className="font-medium underline underline-offset-4"
                                                >
                                                    {occupation.name}
                                                </Link>
                                                <Link
                                                    href={occupation.removeUrl}
                                                    className="text-xs font-normal text-muted-foreground underline underline-offset-4"
                                                >
                                                    {t('guide.compare.remove')}
                                                    <span className="sr-only">
                                                        {`: ${occupation.name}`}
                                                    </span>
                                                </Link>
                                            </div>
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {compare.rows.map((row) => (
                                    <tr
                                        key={row.key}
                                        className="border-b last:border-b-0"
                                    >
                                        <th
                                            scope="row"
                                            className="p-3 text-start align-top font-medium"
                                        >
                                            {row.label}
                                        </th>
                                        {row.cells.map((cell, index) => (
                                            <td
                                                key={index}
                                                className="p-3 align-top"
                                            >
                                                {cell === null ? (
                                                    <span className="text-muted-foreground">
                                                        {t(
                                                            'guide.compare.not_known',
                                                        )}
                                                    </span>
                                                ) : (
                                                    <div className="flex flex-col items-start gap-1">
                                                        {cell.withheld ? (
                                                            <span className="text-muted-foreground">
                                                                {t(
                                                                    'guide.compare.withheld',
                                                                )}
                                                            </span>
                                                        ) : (
                                                            <ValueText
                                                                value={
                                                                    cell.value
                                                                }
                                                            />
                                                        )}
                                                        <StatusBadge
                                                            status={cell.status}
                                                        />
                                                    </div>
                                                )}
                                            </td>
                                        ))}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                <section className="flex flex-col gap-3 rounded-lg border p-4">
                    <h2 className="font-medium">{t('guide.compare.add')}</h2>
                    {full ? (
                        <p className="text-sm text-muted-foreground">
                            {t('guide.compare.full')}
                        </p>
                    ) : (
                        <>
                            <form
                                onSubmit={search}
                                className="flex flex-wrap items-end gap-2"
                            >
                                <div className="flex grow flex-col gap-1">
                                    <Label htmlFor="compare-search">
                                        {t('guide.compare.search')}
                                    </Label>
                                    <input
                                        id="compare-search"
                                        data-test="compare-search"
                                        type="search"
                                        maxLength={100}
                                        value={query}
                                        onChange={(event) =>
                                            setQuery(event.target.value)
                                        }
                                        className="h-10 rounded-md border bg-background px-3"
                                    />
                                </div>
                                <Button type="submit" variant="outline">
                                    {t('guide.compare.search_button')}
                                </Button>
                            </form>
                            {compare.query !== null &&
                                (compare.hits.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        {t('guide.compare.no_hits')}
                                    </p>
                                ) : (
                                    <ul className="flex flex-col divide-y">
                                        {compare.hits.map((hit) => (
                                            <li
                                                key={hit.slug}
                                                className="flex items-center justify-between gap-3 py-2"
                                            >
                                                <span>
                                                    <span lang={hit.nameLocale}>
                                                        {hit.name}
                                                    </span>
                                                    {hit.matchedName !==
                                                        hit.name && (
                                                        <span className="text-sm text-muted-foreground">
                                                            {' '}
                                                            (
                                                            {t(
                                                                'guide.compare.also_called',
                                                                {
                                                                    name: hit.matchedName,
                                                                },
                                                            )}
                                                            )
                                                        </span>
                                                    )}
                                                </span>
                                                <Link
                                                    href={hit.addUrl}
                                                    data-test="compare-add"
                                                    className="rounded-md border px-3 py-1.5 text-sm hover:bg-muted"
                                                >
                                                    {t(
                                                        'guide.compare.add_button',
                                                    )}
                                                    <span className="sr-only">
                                                        {`: ${hit.name}`}
                                                    </span>
                                                </Link>
                                            </li>
                                        ))}
                                    </ul>
                                ))}
                        </>
                    )}
                </section>
            </div>
        </>
    );
}
