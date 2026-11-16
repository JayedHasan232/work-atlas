import { Head, Link } from '@inertiajs/react';
import GroupList from '@/components/occupations/group-list';
import OccupationList from '@/components/occupations/occupation-list';
import { useTranslation } from '@/lib/i18n';
import type { GroupLink, OccupationLink } from '@/types/occupations';

type Props = {
    groups: GroupLink[];
    occupations: OccupationLink[];
    pagination: {
        total: number;
        page: number;
        lastPage: number;
        previous: string | null;
        next: string | null;
    };
};

// The occupation index: the ISCO-08 major groups and every public occupation by name (PLAN M13).
export default function OccupationIndex({
    groups,
    occupations,
    pagination,
}: Props) {
    const { t, formatNumber } = useTranslation();
    const linkClass = 'rounded-md border px-3 py-1.5 hover:bg-accent';

    return (
        <>
            <Head title={t('occupations.index.title')} />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-8 px-4 py-8">
                <header className="flex flex-col gap-3">
                    <h1 className="text-3xl font-semibold">
                        {t('occupations.index.title')}
                    </h1>
                    <p>{t('occupations.index.intro')}</p>
                </header>

                <section
                    aria-labelledby="groups"
                    className="flex flex-col gap-3"
                >
                    <h2 id="groups" className="text-xl font-semibold">
                        {t('occupations.index.groups_heading')}
                    </h2>
                    <GroupList groups={groups} />
                </section>

                <section aria-labelledby="all" className="flex flex-col gap-3">
                    <h2 id="all" className="text-xl font-semibold">
                        {t('occupations.index.all_heading')}{' '}
                        <span className="text-base font-normal text-muted-foreground">
                            (
                            {t('occupations.index.count', {
                                count: formatNumber(pagination.total),
                            })}
                            )
                        </span>
                    </h2>
                    {occupations.length === 0 ? (
                        <p className="text-muted-foreground">
                            {t('occupations.index.empty')}
                        </p>
                    ) : (
                        <OccupationList occupations={occupations} />
                    )}
                    {pagination.lastPage > 1 && (
                        <nav
                            aria-label={t('occupations.index.pagination')}
                            className="flex flex-wrap items-center justify-between gap-2 text-sm"
                        >
                            {pagination.previous !== null ? (
                                <Link
                                    href={pagination.previous}
                                    className={linkClass}
                                >
                                    {t('occupations.index.previous')}
                                </Link>
                            ) : (
                                <span />
                            )}
                            <span>
                                {t('occupations.index.page', {
                                    page: formatNumber(pagination.page),
                                    last: formatNumber(pagination.lastPage),
                                })}
                            </span>
                            {pagination.next !== null ? (
                                <Link
                                    href={pagination.next}
                                    className={linkClass}
                                >
                                    {t('occupations.index.next')}
                                </Link>
                            ) : (
                                <span />
                            )}
                        </nav>
                    )}
                </section>
            </div>
        </>
    );
}
