import { Head, Link, router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Notice, controlClass } from '@/components/moderation/parts';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/lib/i18n';
import { login } from '@/routes';
import type { TalkIndex as Talk } from '@/types/talk';

// An occupation's talk page (PLAN M19): the list of discussions and a form to start one.
export default function TalkIndex({ talk }: { talk: Talk }) {
    const { t, formatDate, formatNumber } = useTranslation();
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        body: '',
        field: talk.selectedField ?? '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(talk.storeUrl, { preserveScroll: true });
    };

    const filter = (field: string) =>
        router.get(window.location.pathname, field === '' ? {} : { field }, {
            preserveScroll: true,
        });

    return (
        <>
            <Head title={t('talk.title', { name: talk.occupation.name })} />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 px-4 py-8">
                <header className="flex flex-col gap-2">
                    <Link
                        href={talk.occupation.url}
                        className="text-sm underline underline-offset-4"
                    >
                        {t('talk.back_to_page')}
                    </Link>
                    <h1 className="text-2xl font-semibold">
                        {t('talk.title', { name: talk.occupation.name })}
                    </h1>
                    <p className="text-muted-foreground">{t('talk.intro')}</p>
                </header>
                <Notice />

                <div className="flex flex-col gap-1">
                    <Label htmlFor="talk-filter">{t('talk.filter')}</Label>
                    <select
                        id="talk-filter"
                        data-test="talk-filter"
                        value={talk.selectedField ?? ''}
                        onChange={(event) => filter(event.target.value)}
                        className={controlClass}
                    >
                        <option value="">{t('talk.all_fields')}</option>
                        {talk.fields.map((field) => (
                            <option key={field.key} value={field.key}>
                                {field.label}
                            </option>
                        ))}
                    </select>
                </div>

                {talk.threads.length === 0 ? (
                    <p className="rounded-lg border p-5">{t('talk.empty')}</p>
                ) : (
                    <ul className="flex flex-col divide-y rounded-lg border">
                        {talk.threads.map((thread) => (
                            <li
                                key={thread.id}
                                className="flex flex-col gap-1 p-4"
                                data-test="talk-thread"
                            >
                                <Link
                                    href={thread.url}
                                    lang={thread.titleLocale}
                                    className="font-medium underline underline-offset-4"
                                >
                                    {thread.title}
                                </Link>
                                <div className="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-muted-foreground">
                                    {thread.hidden && (
                                        <span className="rounded bg-muted px-2 py-0.5 text-xs text-foreground">
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
                                    <span>
                                        {t('talk.by', {
                                            name:
                                                thread.author?.name ??
                                                t('talk.anonymous'),
                                        })}
                                    </span>
                                    <span>
                                        {t('talk.posts', {
                                            count: formatNumber(
                                                thread.postsCount,
                                            ),
                                        })}
                                    </span>
                                    {thread.lastPostedAt !== null && (
                                        <time dateTime={thread.lastPostedAt}>
                                            {t('talk.last_post', {
                                                date: formatDate(
                                                    thread.lastPostedAt,
                                                    { dateStyle: 'medium' },
                                                ),
                                            })}
                                        </time>
                                    )}
                                </div>
                            </li>
                        ))}
                    </ul>
                )}

                <section className="flex flex-col gap-3 rounded-lg border p-4">
                    <h2 className="font-medium">{t('talk.form.new')}</h2>
                    {!talk.viewer.signedIn ? (
                        <Link
                            href={login()}
                            className="text-sm underline underline-offset-4"
                        >
                            {t('talk.sign_in')}
                        </Link>
                    ) : !talk.viewer.canPost ? (
                        <p className="text-sm text-muted-foreground">
                            {t('talk.cannot_post')}
                        </p>
                    ) : (
                        <form onSubmit={submit} className="flex flex-col gap-3">
                            <div className="flex flex-col gap-1">
                                <Label htmlFor="talk-title">
                                    {t('talk.form.title')}
                                </Label>
                                <input
                                    id="talk-title"
                                    data-test="talk-title"
                                    maxLength={200}
                                    required
                                    value={data.title}
                                    onChange={(event) =>
                                        setData('title', event.target.value)
                                    }
                                    className={controlClass}
                                />
                                <InputError message={errors.title} />
                            </div>
                            <div className="flex flex-col gap-1">
                                <Label htmlFor="talk-field">
                                    {t('talk.form.field')}
                                </Label>
                                <select
                                    id="talk-field"
                                    value={data.field}
                                    onChange={(event) =>
                                        setData('field', event.target.value)
                                    }
                                    className={controlClass}
                                >
                                    <option value="">
                                        {t('talk.all_fields')}
                                    </option>
                                    {talk.fields.map((field) => (
                                        <option
                                            key={field.key}
                                            value={field.key}
                                        >
                                            {field.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.field} />
                            </div>
                            <div className="flex flex-col gap-1">
                                <Label htmlFor="talk-body">
                                    {t('talk.form.body')}
                                </Label>
                                <textarea
                                    id="talk-body"
                                    data-test="talk-body"
                                    rows={5}
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
                            </div>
                            <div>
                                <Button
                                    type="submit"
                                    data-test="talk-start"
                                    disabled={processing}
                                >
                                    {t('talk.form.start')}
                                </Button>
                            </div>
                        </form>
                    )}
                </section>
            </div>
        </>
    );
}
