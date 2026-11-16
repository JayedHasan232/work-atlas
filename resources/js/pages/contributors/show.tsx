import { Head, Link } from '@inertiajs/react';
import { useTranslation } from '@/lib/i18n';
import type { OccupationLink } from '@/types/occupations';

type Props = {
    contributor: {
        name: string;
        since: string | null;
        contributions: number;
        occupations: OccupationLink[];
    };
};

// A contributor's public profile (PLAN M16): credit computed from the revisions in the record.
export default function ContributorPage({ contributor }: Props) {
    const { t, formatNumber, formatDate } = useTranslation();

    return (
        <>
            <Head title={contributor.name} />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 px-4 py-8">
                <header className="flex flex-col gap-2">
                    <h1 className="text-3xl font-semibold">
                        {contributor.name}
                    </h1>
                    {contributor.since !== null && (
                        <p className="text-muted-foreground">
                            {t('contribute.profile.since', {
                                date: formatDate(contributor.since, {
                                    year: 'numeric',
                                    month: 'long',
                                }),
                            })}
                        </p>
                    )}
                    <p>
                        {t('contribute.profile.contributions', {
                            count: formatNumber(contributor.contributions),
                        })}
                    </p>
                    <p className="text-sm text-muted-foreground">
                        {t('contribute.profile.note')}
                    </p>
                </header>

                <section
                    aria-labelledby="occupations"
                    className="flex flex-col gap-3"
                >
                    <h2 id="occupations" className="text-xl font-semibold">
                        {t('contribute.profile.occupations')}
                    </h2>
                    {contributor.occupations.length === 0 ? (
                        <p className="text-muted-foreground">
                            {t('contribute.profile.no_occupations')}
                        </p>
                    ) : (
                        <ul className="flex flex-col gap-2">
                            {contributor.occupations.map((occupation) => (
                                <li key={occupation.slug}>
                                    <Link
                                        href={occupation.url}
                                        lang={occupation.nameLocale}
                                        className="underline underline-offset-4"
                                    >
                                        {occupation.name}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
            </div>
        </>
    );
}
