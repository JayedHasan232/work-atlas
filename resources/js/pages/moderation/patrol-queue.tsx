import { Head, Link } from '@inertiajs/react';
import {
    Flags,
    ModerationTabs,
    Notice,
    PersonName,
} from '@/components/moderation/parts';
import { useTranslation } from '@/lib/i18n';
import type { PatrolQueueEntry } from '@/types/moderation';

// Recent edits by people that nobody has looked at yet (PLAN M18); possibly promotional ones first.
export default function PatrolQueue({
    entries,
}: {
    entries: PatrolQueueEntry[];
}) {
    const { t, formatDate } = useTranslation();

    return (
        <>
            <Head title={t('moderation.patrol.title')} />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 px-4 py-8">
                <ModerationTabs active="patrol" />
                <header className="flex flex-col gap-2">
                    <h1 className="text-2xl font-semibold">
                        {t('moderation.patrol.title')}
                    </h1>
                    <p className="text-muted-foreground">
                        {t('moderation.patrol.intro')}
                    </p>
                </header>
                <Notice />

                {entries.length === 0 ? (
                    <p className="rounded-lg border p-5">
                        {t('moderation.patrol.empty')}
                    </p>
                ) : (
                    <ul className="flex flex-col divide-y rounded-lg border">
                        {entries.map((entry) => (
                            <li
                                key={entry.revisionId}
                                className="flex flex-col gap-2 p-4"
                                data-test="patrol-entry"
                            >
                                <div className="flex flex-wrap items-center gap-2">
                                    <span className="font-medium">
                                        {entry.occupation?.name ?? '—'}
                                    </span>
                                    <span className="text-muted-foreground">
                                        · {entry.field}
                                    </span>
                                    <span className="rounded bg-muted px-2 py-0.5 text-xs">
                                        {t(`moderation.kinds.${entry.kind}`)}
                                    </span>
                                </div>
                                <div className="flex flex-wrap items-center gap-3 text-sm text-muted-foreground">
                                    <PersonName person={entry.author} />
                                    {entry.createdAt !== null && (
                                        <time dateTime={entry.createdAt}>
                                            {formatDate(entry.createdAt, {
                                                dateStyle: 'medium',
                                                timeStyle: 'short',
                                            })}
                                        </time>
                                    )}
                                    <Link
                                        href={entry.url}
                                        className="ml-auto text-foreground underline underline-offset-4"
                                    >
                                        {t('moderation.patrol.open')}
                                    </Link>
                                </div>
                                <Flags flags={entry.flags} />
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}
