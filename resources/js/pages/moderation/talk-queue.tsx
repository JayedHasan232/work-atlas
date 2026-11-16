import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import {
    Flags,
    ModerationTabs,
    Notice,
    PersonName,
} from '@/components/moderation/parts';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/lib/i18n';
import type { TalkQueuePost } from '@/types/talk';

// Recent talk-page posts for moderators (PLAN M19); posts with links or organisation names first.
export default function TalkQueue({ posts }: { posts: TalkQueuePost[] }) {
    const { t, formatDate } = useTranslation();
    const [sending, setSending] = useState(false);

    return (
        <>
            <Head title={t('talk.queue.title')} />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 px-4 py-8">
                <ModerationTabs active="talk" />
                <header className="flex flex-col gap-2">
                    <h1 className="text-2xl font-semibold">
                        {t('talk.queue.title')}
                    </h1>
                    <p className="text-muted-foreground">
                        {t('talk.queue.intro')}
                    </p>
                </header>
                <Notice />

                {posts.length === 0 ? (
                    <p className="rounded-lg border p-5">
                        {t('talk.queue.empty')}
                    </p>
                ) : (
                    <ul className="flex flex-col divide-y rounded-lg border">
                        {posts.map((item) => (
                            <li
                                key={item.postId}
                                className="flex flex-col gap-2 p-4"
                                data-test="talk-queue-post"
                            >
                                <div className="flex flex-wrap items-center gap-2">
                                    <span className="font-medium">
                                        {item.occupation.name}
                                    </span>
                                    <span className="text-muted-foreground">
                                        · {item.threadTitle}
                                    </span>
                                </div>
                                <p
                                    lang={item.locale}
                                    className="text-sm break-words whitespace-pre-line"
                                >
                                    {item.body}
                                </p>
                                <Flags flags={item.flags} />
                                <div className="flex flex-wrap items-center gap-3 text-sm text-muted-foreground">
                                    <PersonName person={item.author} />
                                    {item.createdAt !== null && (
                                        <time dateTime={item.createdAt}>
                                            {formatDate(item.createdAt, {
                                                dateStyle: 'medium',
                                                timeStyle: 'short',
                                            })}
                                        </time>
                                    )}
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        disabled={sending}
                                        onClick={() =>
                                            router.post(
                                                item.hideUrl,
                                                {},
                                                {
                                                    preserveScroll: true,
                                                    onStart: () =>
                                                        setSending(true),
                                                    onFinish: () =>
                                                        setSending(false),
                                                },
                                            )
                                        }
                                    >
                                        {t('talk.moderation.hide')}
                                    </Button>
                                    <Link
                                        href={item.threadUrl}
                                        className="ml-auto text-foreground underline underline-offset-4"
                                    >
                                        {t('talk.queue.open')}
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
