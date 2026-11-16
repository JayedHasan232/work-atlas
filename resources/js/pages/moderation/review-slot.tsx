import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import {
    Diff,
    EditNotes,
    ModerationTabs,
    Notice,
    PersonName,
    ShowValue,
    controlClass,
} from '@/components/moderation/parts';
import StatusBadge from '@/components/occupations/status-badge';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/lib/i18n';
import type { ReviewSlot as Slot } from '@/types/moderation';
import type { FieldStatus } from '@/types/occupations';

// One value on the review desk (PLAN M18): accept or reject each suggested change, or set the status.
export default function ReviewSlot({ slot }: { slot: Slot }) {
    const { t } = useTranslation();
    const [comments, setComments] = useState<Record<number, string>>({});
    const [status, setStatus] = useState<FieldStatus | ''>('');
    const [sending, setSending] = useState(false);
    const options = {
        preserveScroll: true,
        onStart: () => setSending(true),
        onFinish: () => setSending(false),
    };

    const decide = (url: string, revisionId: number, decision: string) =>
        router.post(
            url,
            { decision, comment: comments[revisionId] ?? null },
            options,
        );

    return (
        <>
            <Head
                title={`${slot.field.label} · ${t('moderation.review.title')}`}
            />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 px-4 py-8">
                <ModerationTabs active="review" />
                <header className="flex flex-col gap-2">
                    {slot.occupation !== null && (
                        <Link
                            href={slot.occupation.url}
                            className="text-sm underline underline-offset-4"
                        >
                            {slot.occupation.name}
                        </Link>
                    )}
                    <h1 className="text-2xl font-semibold">
                        {slot.field.label}
                        {slot.locale !== null && (
                            <span className="text-muted-foreground">
                                {' '}
                                ({slot.locale})
                            </span>
                        )}
                    </h1>
                    {slot.field.help !== null && (
                        <p className="text-sm text-muted-foreground">
                            {slot.field.help}
                        </p>
                    )}
                    <div className="flex flex-wrap items-center gap-2 text-sm">
                        <StatusBadge status={slot.status} />
                        {slot.reason !== null && (
                            <span className="rounded bg-muted px-2 py-0.5">
                                {t(`moderation.reasons.${slot.reason}`)}
                            </span>
                        )}
                        {slot.crossCheck !== null && (
                            <span className="text-muted-foreground">
                                {t('moderation.review.cross_check', {
                                    result: t(
                                        `moderation.cross_checks.${slot.crossCheck}`,
                                    ),
                                })}
                            </span>
                        )}
                    </div>
                </header>
                <Notice />

                <section className="flex flex-col gap-2 rounded-lg border p-4">
                    <h2 className="font-medium">
                        {t('moderation.review.current')}
                    </h2>
                    <ShowValue
                        field={slot.field}
                        value={slot.current}
                        status={slot.status}
                    />
                    {slot.historyUrl !== null && (
                        <Link
                            href={slot.historyUrl}
                            className="text-sm underline underline-offset-4"
                        >
                            {t('moderation.review.history')}
                        </Link>
                    )}
                </section>

                {slot.proposals.length === 0 ? (
                    <p className="text-muted-foreground">
                        {t('moderation.review.no_proposals')}
                    </p>
                ) : (
                    slot.proposals.map((proposal) => (
                        <section
                            key={proposal.revisionId}
                            className="flex flex-col gap-3 rounded-lg border p-4"
                            data-test="proposal"
                        >
                            <h2 className="font-medium">
                                {t('moderation.review.proposal')} ·{' '}
                                <PersonName person={proposal.author} />
                            </h2>
                            <ShowValue
                                field={slot.field}
                                value={proposal.value}
                                status={slot.status}
                            />
                            <Diff lines={proposal.diff} />
                            <EditNotes edit={proposal} />
                            <textarea
                                aria-label={t('moderation.review.comment')}
                                placeholder={t('moderation.review.comment')}
                                rows={2}
                                maxLength={2000}
                                value={comments[proposal.revisionId] ?? ''}
                                onChange={(event) =>
                                    setComments({
                                        ...comments,
                                        [proposal.revisionId]:
                                            event.target.value,
                                    })
                                }
                                className={controlClass}
                            />
                            <div className="flex flex-wrap gap-2">
                                <Button
                                    data-test="accept"
                                    disabled={sending}
                                    onClick={() =>
                                        decide(
                                            proposal.reviewUrl,
                                            proposal.revisionId,
                                            'accepted',
                                        )
                                    }
                                >
                                    {t('moderation.review.accept')}
                                </Button>
                                <Button
                                    data-test="reject"
                                    variant="outline"
                                    disabled={sending}
                                    onClick={() =>
                                        decide(
                                            proposal.reviewUrl,
                                            proposal.revisionId,
                                            'rejected',
                                        )
                                    }
                                >
                                    {t('moderation.review.reject')}
                                </Button>
                            </div>
                        </section>
                    ))
                )}

                {slot.statuses.length > 0 && (
                    <section className="flex flex-col gap-3 rounded-lg border p-4">
                        <h2 className="font-medium">
                            {t('moderation.review.set_status')}
                        </h2>
                        <select
                            aria-label={t('moderation.review.set_status')}
                            data-test="status"
                            value={status}
                            onChange={(event) =>
                                setStatus(event.target.value as FieldStatus)
                            }
                            className={controlClass}
                        >
                            <option value="" />
                            {slot.statuses.map((option) => (
                                <option key={option} value={option}>
                                    {t(`occupations.status.${option}`)}
                                </option>
                            ))}
                        </select>
                        <div>
                            <Button
                                data-test="set-status"
                                variant="outline"
                                disabled={sending || status === ''}
                                onClick={() =>
                                    router.post(
                                        slot.statusUrl,
                                        { status },
                                        options,
                                    )
                                }
                            >
                                {t('moderation.review.set')}
                            </Button>
                        </div>
                    </section>
                )}
            </div>
        </>
    );
}
