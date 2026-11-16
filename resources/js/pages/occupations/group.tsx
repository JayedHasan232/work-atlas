import { Head, Link } from '@inertiajs/react';
import GroupList from '@/components/occupations/group-list';
import OccupationList from '@/components/occupations/occupation-list';
import { useTranslation } from '@/lib/i18n';
import type { GroupLink, OccupationLink } from '@/types/occupations';

type Props = {
    group: GroupLink;
    path: GroupLink[];
    subgroups: GroupLink[];
    occupations: OccupationLink[];
};

// One ISCO-08 group: its child groups, or for a unit group its public occupations (PLAN M13).
export default function OccupationGroup({
    group,
    path,
    subgroups,
    occupations,
}: Props) {
    const { t, localizePath } = useTranslation();
    const isUnitGroup = group.level === 4;

    return (
        <>
            <Head title={group.title} />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-8 px-4 py-8">
                <header className="flex flex-col gap-3">
                    <nav aria-label={t('common.ui.breadcrumb')}>
                        <ol className="flex flex-wrap gap-x-2 gap-y-1 text-sm text-muted-foreground">
                            <li>
                                <Link
                                    href={localizePath('/occupations')}
                                    className="underline underline-offset-4 hover:text-foreground"
                                >
                                    {t('occupations.group.all')}
                                </Link>
                            </li>
                            {path
                                .filter((parent) => parent.code !== group.code)
                                .map((parent) => (
                                    <li
                                        key={parent.code}
                                        className="flex gap-2"
                                    >
                                        <span aria-hidden="true">›</span>
                                        <Link
                                            href={parent.url}
                                            lang={parent.titleLocale}
                                            className="underline underline-offset-4 hover:text-foreground"
                                        >
                                            {parent.title}
                                        </Link>
                                    </li>
                                ))}
                        </ol>
                    </nav>
                    <h1
                        className="text-3xl font-semibold"
                        lang={group.titleLocale}
                    >
                        {group.title}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {t('occupations.group.code', { code: group.code })}
                    </p>
                </header>

                {isUnitGroup ? (
                    <section
                        aria-labelledby="occupations"
                        className="flex flex-col gap-3"
                    >
                        <h2 id="occupations" className="text-xl font-semibold">
                            {t('occupations.group.occupations_heading')}
                        </h2>
                        {occupations.length === 0 ? (
                            <p className="text-muted-foreground">
                                {t('occupations.group.empty')}
                            </p>
                        ) : (
                            <OccupationList occupations={occupations} />
                        )}
                    </section>
                ) : (
                    <section
                        aria-labelledby="children"
                        className="flex flex-col gap-3"
                    >
                        <h2 id="children" className="text-xl font-semibold">
                            {t('occupations.group.children_heading')}
                        </h2>
                        <GroupList groups={subgroups} />
                    </section>
                )}
            </div>
        </>
    );
}
