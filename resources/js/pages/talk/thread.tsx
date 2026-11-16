import { Head, Link, router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Flags, Notice, controlClass } from '@/components/moderation/parts';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/lib/i18n';
import type { TalkThread as Thread } from '@/types/talk';

// One discussion on an occupation's talk page (PLAN M19). Moderators hide or show posts; nothing is deleted.
export default function TalkThread({ talk }: { talk: Thread }) {
    const { t, formatDate, localizePath } = useTranslation();
    const { thread, posts, viewer } = talk;
    const [reason, setReason] = useState('');
    const [sending, setSending] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        body: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (talk.replyUrl !== null) {
            post(talk.replyUrl, {
                preserveScroll: true,
                onSuccess: () => reset(),
            });
        }
    };

    const moderate = (url: string) =>
        router.post(
            url,
            { reason: reason || null },
            {
                preserveScroll: true,
                onStart: () => setSending(true),
                onFinish: () => setSending(false),
            },
        );

    const title = thread.title ?? t('talk.hidden.thread');

    return (
        <>
            <Head title={`${title} · ${talk.occupation.name}`} />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 px-4 py-8">
                <header className="flex flex-col gap-2">
                    <div className="flex flex-wrap gap-4 text-sm">
                        <Link
                            href={talk.occupation.url}
                            className="underline underline-offset-4"
                        >
                            {talk.occupation.name}
                        </Link>
                        <Link
                            href={talk.talkUrl}
                            className="underline underline-offset-4"
                        >
                            {t('talk.back_to_talk')}
                        </Link>
                    </div>
                    <h1
                        className="text-2xl font-semibold"
                        lang={
                            thread.title !== null
                                ? thread.titleLocale
                                : undefined
                        }
                    >
                        {title}
                    </h1>
                    <div className="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-muted-foreground">
                        {thread.hidden && (
                            <span
                                className="rounded bg-muted px-2 py-0.5 text-xs text-foreground"
                                data-test="thread-hidden"
                            >
                                {t('talk.moderation.hidden_badge')}
                            </span>
                        )}
                        {thread.field !== null && (
                            <span>
                                {t('talk.about_field', {
                                    field: thread.field.label,
                                })}
                            </span>
                        )}
                        {thread.createdAt !== null && (
                            <time dateTime={thread.createdAt}>
                                {formatDate(thread.createdAt, {
                                    dateStyle: 'medium',
                                })}
                            </time>
                        )}
                    </div>
                </header>
                <Notice />

                {viewer.canModerate && (
                    <section className="flex flex-col gap-2 rounded-lg border border-dashed p-4">
                        <input
                            aria-label={t('talk.moderation.reason')}
                            placeholder={t('talk.moderation.reason')}
                            maxLength={500}
                            value={reason}
                            onChange={(event) => setReason(event.target.value)}
                            className={controlClass}
                        />
                        <div>
                            {thread.hidden && thread.showUrl !== null ? (
                                <Button
                                    variant="outline"
                                    data-test="thread-show"
                                    disabled={sending}
                                    onClick={() => moderate(thread.showUrl!)}
                                >
                                    {t('talk.moderation.show')}
                                </Button>
                            ) : (
                                thread.hideUrl !== null && (
                                    <Button
                                        variant="destructive"
                                        data-test="thread-hide"
                                        disabled={sending}
                                        onClick={() =>
                                            moderate(thread.hideUrl!)
                                        }
                                    >
                                        {t('talk.moderation.hide')}
                                    </Button>
                                )
                            )}
                        </div>
                    </section>
                )}

                <ol className="flex flex-col gap-3">
                    {posts.map((item) => (
                        <li
                            key={item.id}
                            id={`post-${item.id}`}
                            className="flex flex-col gap-2 rounded-lg border p-4"
                            data-test="talk-post"
                        >
                            <div className="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-muted-foreground">
                                {item.author !== null ? (
                                    <Link
                                        href={item.author.url}
                                        className="font-medium text-foreground underline underline-offset-4"
                                    >
                                        {item.author.name}
                                    </Link>
                                ) : (
                                    !item.hidden && (
                                        <span>{t('talk.anonymous')}</span>
                                    )
                                )}
                                {item.createdAt !== null && (
                                    <time dateTime={item.createdAt}>
                                        {formatDate(item.createdAt, {
                                            dateStyle: 'medium',
                                            timeStyle: 'short',
                                        })}
                                    </time>
                                )}
                                {item.hidden && (
                                    <span className="rounded bg-muted px-2 py-0.5 text-xs text-foreground">
                                        {t('talk.moderation.hidden_badge')}
                                    </span>
                                )}
                            </div>
                            {item.body !== null ? (
                                <p
                                    lang={item.locale}
                                    className="break-words whitespace-pre-line"
                                    data-test="talk-post-body"
                                >
                                    {item.body}
                                </p>
                            ) : (
                                <p className="text-sm text-muted-foreground italic">
                                    {t('talk.hidden.post')}
                                </p>
                            )}
                            <Flags flags={item.flags} />
                            {item.hidden && item.showUrl !== null ? (
                                <div>
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        data-test="post-show"
                                        disabled={sending}
                                        onClick={() => moderate(item.showUrl!)}
                                    >
                                        {t('talk.moderation.show')}
                                    </Button>
                                </div>
                            ) : (
                                item.hideUrl !== null && (
                                    <div>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            data-test="post-hide"
                                            disabled={sending}
                                            onClick={() =>
                                                moderate(item.hideUrl!)
                                            }
                                        >
                                            {t('talk.moderation.hide')}
                                        </Button>
                                    </div>
                                )
                            )}
                        </li>
                    ))}
                </ol>

                <section className="flex flex-col gap-3 rounded-lg border p-4">
                    <h2 className="font-medium">{t('talk.form.reply')}</h2>
                    {talk.replyUrl === null ? (
                        <p className="text-sm text-muted-foreground">
                            {t('talk.hidden.closed')}
                        </p>
                    ) : !viewer.signedIn ? (
                        <Link
                            href={localizePath('/login')}
                            className="text-sm underline underline-offset-4"
                        >
                            {t('talk.sign_in')}
                        </Link>
                    ) : !viewer.canPost ? (
                        <p className="text-sm text-muted-foreground">
                            {t('talk.cannot_post')}
                        </p>
                    ) : (
                        <form onSubmit={submit} className="flex flex-col gap-3">
                            <Label htmlFor="talk-reply" className="sr-only">
                                {t('talk.form.body')}
                            </Label>
                            <textarea
                                id="talk-reply"
                                data-test="talk-reply"
                                rows={4}
                                maxLength={5000}
                                required
                                value={data.body}
                                onChange={(event) =>
                                    setData('body', event.target.value)
                                }
                                className={controlClass}
                            />
                            <p className="text-xs text-muted-foreground">
                                {t('talk.form.hint')}
                            </p>
                            <InputError message={errors.body} />
                            <div>
                                <Button
                                    type="submit"
                                    data-test="talk-send"
                                    disabled={processing}
                                >
                                    {t('talk.form.send_reply')}
                                </Button>
                            </div>
                        </form>
                    )}
                </section>
            </div>
        </>
    );
}
