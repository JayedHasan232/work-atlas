import { Link } from '@inertiajs/react';
import { useTranslation } from '@/lib/i18n';
import type { GroupLink } from '@/types/occupations';

/** ISCO-08 groups, each with the number of public occupations under it. */
export default function GroupList({ groups }: { groups: GroupLink[] }) {
    const { t, formatNumber } = useTranslation();

    return (
        <ul className="grid gap-2 sm:grid-cols-2">
            {groups.map((group) => (
                <li key={group.code}>
                    <Link
                        href={group.url}
                        className="flex h-full flex-col gap-1 rounded-md border p-3 hover:bg-accent"
                    >
                        <span className="font-medium" lang={group.titleLocale}>
                            {group.title}
                        </span>
                        <span className="text-sm text-muted-foreground">
                            {t('occupations.group.code', { code: group.code })}
                            {group.occupations !== undefined &&
                                group.occupations !== null && (
                                    <>
                                        {' · '}
                                        {t('occupations.index.count', {
                                            count: formatNumber(
                                                group.occupations,
                                            ),
                                        })}
                                    </>
                                )}
                        </span>
                    </Link>
                </li>
            ))}
        </ul>
    );
}
