import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import {
    Diff,
    EditNotes,
    Flags,
    ModerationTabs,
    Notice,
    PersonName,
    ShowValue,
    controlClass,
} from '@/components/moderation/parts';
import StatusBadge from '@/components/occupations/status-badge';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/lib/i18n';
import type { PatrolEdit as Edit } from '@/types/moderation';

// One edit on patrol (PLAN M18): before and after, the diff, why it was flagged, and one-click revert.
export default function PatrolEdit({ edit }: { edit: Edit }) {
    const { t, formatDate } = useTranslation();
    const [comment, setComment] = useState('');
    const [confirming, setConfirming] = useState(false);
    const [sending, setSending] = useState(false);
    const options = {
        onStart: () => setSending(true),
        onFinish: () => setSending(false),
    };

    return (
        <>
            <Head
                title={`${edit.field.label} · ${t('moderation.patrol.title')}`}
            />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 px-4 py-8">
                <ModerationTabs active="patrol" />
                <header className="flex flex-col gap-2">
                    {edit.occupation !== null && (
                        <Link
                            href={edit.occupation.url}
                            className="text-sm underline underline-offset-4"
                        >
                            {edit.occupation.name}
                        </Link>
                    )}
                    <h1 className="text-2xl font-semibold">
                        {edit.field.label}
                        {edit.locale !== null && (
                            <span className="text-muted-foreground">
                                {' '}
                                ({edit.locale})
                            </span>
                        )}
                    </h1>
                    <div className="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                        <PersonName person={edit.author} />
                        <span className="rounded bg-muted px-2 py-0.5 text-xs text-foreground">
                            {t(`moderation.kinds.${edit.kind}`)}
                        </span>
                        <StatusBadge status={edit.status} />
                        {edit.createdAt !== null && (
                            <time dateTime={edit.createdAt}>
                                {formatDate(edit.createdAt, {
                                    dateStyle: 'medium',
                                    timeStyle: 'short',
                                })}
                            </time>
                        )}
                    </div>
                </header>
                <Notice />

                {edit.flags.length > 0 && (
                    <section className="flex flex-col gap-2">
                        <h2 className="text-sm font-medium">
                            {t('moderation.patrol.flags')}
                        </h2>
                        <Flags flags={edit.flags} />
                    </section>
                )}

                <div className="grid gap-4 md:grid-cols-2">
                    <section className="flex flex-col gap-2 rounded-lg border p-4">
                        <h2 className="text-sm font-medium">
                            {t('moderation.patrol.before')}
                        </h2>
                        <ShowValue
                            field={edit.field}
                            value={edit.old}
                            status={edit.status}
                        />
                    </section>
                    <section className="flex flex-col gap-2 rounded-lg border p-4">
                        <h2 className="text-sm font-medium">
                            {t('moderation.patrol.after')}
                        </h2>
                        <ShowValue
                            field={edit.field}
                            value={edit.value}
                            status={edit.status}
                        />
                    </section>
                </div>
                <Diff lines={edit.diff} />
                <EditNotes edit={edit} />

                <section className="flex flex-col gap-3 rounded-lg border p-4">
                    {edit.patrolled ? (
                        <p className="text-sm text-muted-foreground">
                            {t('moderation.patrol.patrolled')}
                        </p>
                    ) : (
                        <div>
                            <Button
                                data-test="mark-patrolled"
                                variant="outline"
                                disabled={sending}
                                onClick={() =>
                                    router.post(edit.patrolUrl, {}, options)
                                }
                            >
                                {t('moderation.patrol.mark')}
                            </Button>
                        </div>
                    )}

                    {!edit.current ? (
                        <p className="text-sm text-muted-foreground">
                            {t('moderation.patrol.not_current')}
                        </p>
                    ) : !edit.canRevert ? (
                        <p className="text-sm text-muted-foreground">
                            {t('moderation.patrol.cannot_revert')}
                        </p>
                    ) : confirming ? (
                        <div className="flex flex-col gap-2">
                            <textarea
                                aria-label={t(
                                    'moderation.patrol.revert_comment',
                                )}
                                placeholder={t(
                                    'moderation.patrol.revert_comment',
                                )}
                                rows={2}
                                maxLength={2000}
                                value={comment}
                                onChange={(event) =>
                                    setComment(event.target.value)
                                }
                                className={controlClass}
                            />
                            <div>
                                <Button
                                    data-test="revert-confirm"
                                    variant="destructive"
                                    disabled={sending}
                                    onClick={() =>
                                        router.post(
                                            edit.revertUrl,
                                            { comment: comment || null },
                                            options,
                                        )
                                    }
                                >
                                    {t('moderation.patrol.revert_confirm')}
                                </Button>
                            </div>
                        </div>
                    ) : (
                        <div>
                            <Button
                                data-test="revert"
                                variant="destructive"
                                onClick={() => setConfirming(true)}
                            >
                                {t('moderation.patrol.revert')}
                            </Button>
                        </div>
                    )}

                    <Link
                        href={edit.slotUrl}
                        className="text-sm underline underline-offset-4"
                    >
                        {t('moderation.patrol.see_value')}
                    </Link>
                </section>
            </div>
        </>
    );
}
