import { Head, Link } from '@inertiajs/react';
import { ModerationTabs, Notice } from '@/components/moderation/parts';
import StatusBadge from '@/components/occupations/status-badge';
import { useTranslation } from '@/lib/i18n';
import type { ReviewQueueItem } from '@/types/moderation';

// The review queue for reviewers and above (PLAN M18): disagreements first, sensitive values included.
export default function ReviewQueue({ items }: { items: ReviewQueueItem[] }) {
    const { t, formatNumber } = useTranslation();

    return (
        <>
            <Head title={t('moderation.review.title')} />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 px-4 py-8">
                <ModerationTabs active="review" />
                <header className="flex flex-col gap-2">
                    <h1 className="text-2xl font-semibold">
                        {t('moderation.review.title')}
                    </h1>
                    <p className="text-muted-foreground">
                        {t('moderation.review.intro')}
                    </p>
                </header>
                <Notice />

                {items.length === 0 ? (
                    <p className="rounded-lg border p-5">
                        {t('moderation.review.empty')}
                    </p>
                ) : (
                    <ul className="flex flex-col divide-y rounded-lg border">
                        {items.map((item) => (
                            <li
                                key={item.fieldValueId}
                                className="flex flex-col gap-2 p-4"
                                data-test="review-item"
                            >
                                <div className="flex flex-wrap items-center gap-2">
                                    <span className="font-medium">
                                        {item.occupation?.name ?? '—'}
                                    </span>
                                    <span className="text-muted-foreground">
                                        · {item.field}
                                        {item.locale !== null &&
                                            ` (${item.locale})`}
                                    </span>
                                    <StatusBadge status={item.status} />
                                </div>
                                <div className="flex flex-wrap items-center gap-3 text-sm">
                                    {item.reason !== null && (
                                        <span className="rounded bg-muted px-2 py-0.5">
                                            {t(
                                                `moderation.reasons.${item.reason}`,
                                            )}
                                        </span>
                                    )}
                                    {item.proposals > 0 && (
                                        <span className="text-muted-foreground">
                                            {t('moderation.review.proposals', {
                                                count: formatNumber(
                                                    item.proposals,
                                                ),
                                            })}
                                        </span>
                                    )}
                                    <Link
                                        href={item.url}
                                        className="ml-auto underline underline-offset-4"
                                    >
                                        {t('moderation.review.open')}
                                    </Link>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}
