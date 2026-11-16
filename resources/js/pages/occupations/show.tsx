import { Head, Link, usePage } from '@inertiajs/react';
import FieldValue from '@/components/occupations/field-value';
import StatusBadge from '@/components/occupations/status-badge';
import { useTranslation } from '@/lib/i18n';
import type { PublicOccupation } from '@/types/occupations';
import type { TranslationKey } from '@/types/translation-keys.generated';

const CONTENT_LICENSE_URL = 'https://creativecommons.org/licenses/by-sa/4.0/';

const noticeClass =
    'rounded-md border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100';

// The public page of one occupation (PLAN M13). Every value shows how far it has been checked; a
// sensitive value that is not verified arrives without its value (OccupationPresenter).
export default function OccupationShow({
    occupation,
}: {
    occupation: PublicOccupation;
}) {
    const { t, formatNumber, formatDate } = useTranslation();
    const { notice } = usePage().props;

    return (
        <>
            <Head title={occupation.name} />
            <article className="mx-auto flex w-full max-w-3xl flex-col gap-8 px-4 py-8">
                {typeof notice === 'string' && (
                    <p
                        role="status"
                        className="rounded-md border border-emerald-300 bg-emerald-50 p-3 text-sm text-emerald-900 dark:border-emerald-700 dark:bg-emerald-950 dark:text-emerald-100"
                    >
                        {t(notice as TranslationKey)}
                    </p>
                )}
                <header className="flex flex-col gap-3">
                    {occupation.groups.length > 0 && (
                        <nav aria-label={t('common.ui.breadcrumb')}>
                            <ol className="flex flex-wrap gap-x-2 gap-y-1 text-sm text-muted-foreground">
                                {occupation.groups.map((group, index) => (
                                    <li key={group.code} className="flex gap-2">
                                        {index > 0 && (
                                            <span aria-hidden="true">›</span>
                                        )}
                                        <Link
                                            href={group.url}
                                            lang={group.titleLocale}
                                            className="underline underline-offset-4 hover:text-foreground"
                                        >
                                            {group.title}
                                        </Link>
                                    </li>
                                ))}
                            </ol>
                        </nav>
                    )}
                    <h1
                        className="text-3xl font-semibold"
                        lang={occupation.nameLocale}
                    >
                        {occupation.name}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {t('occupations.show.isco', {
                            code: occupation.iscoCode,
                        })}
                    </p>
                    {occupation.unverified && (
                        <p role="note" className={noticeClass}>
                            {t('occupations.show.draft_notice')}
                        </p>
                    )}
                </header>

                {occupation.names.length > 0 && (
                    <section
                        aria-labelledby="names"
                        className="flex flex-col gap-3"
                    >
                        <h2 id="names" className="text-xl font-semibold">
                            {t('occupations.show.names_heading')}
                        </h2>
                        {occupation.names.map((list) => (
                            <div
                                key={list.locale}
                                className="flex flex-col gap-2 rounded-md border p-3"
                            >
                                <div className="flex justify-end">
                                    <StatusBadge status={list.status} />
                                </div>
                                <ul
                                    className="flex flex-col gap-1"
                                    lang={list.locale}
                                >
                                    {list.items.map((item) => (
                                        <li key={`${item.type}-${item.name}`}>
                                            <span className="font-medium">
                                                {item.name}
                                            </span>{' '}
                                            <span className="text-sm text-muted-foreground">
                                                (
                                                {t(
                                                    `occupations.name_types.${item.type}`,
                                                )}
                                                {item.region !== null &&
                                                    `, ${item.region}`}
                                                )
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        ))}
                    </section>
                )}

                {occupation.sections.map((section) => (
                    <section
                        key={section.key}
                        aria-labelledby={`group-${section.key}`}
                        className="flex flex-col gap-3"
                    >
                        <h2
                            id={`group-${section.key}`}
                            className="text-xl font-semibold"
                        >
                            {section.title}
                        </h2>
                        {section.medical && (
                            <p role="note" className={noticeClass}>
                                {t('occupations.show.medical_notice')}
                            </p>
                        )}
                        <dl className="flex flex-col divide-y rounded-md border">
                            {section.fields.map((field) => (
                                <div
                                    key={field.key}
                                    className="flex flex-col gap-1 p-3"
                                    data-field={field.key}
                                >
                                    <dt className="flex flex-wrap items-start justify-between gap-2">
                                        <span className="font-medium">
                                            {field.label}
                                        </span>
                                        {field.status !== null && (
                                            <StatusBadge
                                                status={field.status}
                                            />
                                        )}
                                    </dt>
                                    <dd className="flex flex-col gap-1">
                                        <FieldValue field={field} />
                                        {field.help !== null && (
                                            <p className="text-sm text-muted-foreground">
                                                {field.help}
                                            </p>
                                        )}
                                        {field.sources.length > 0 && (
                                            <p className="text-sm">
                                                {field.sources.map((number) => (
                                                    <a
                                                        key={number}
                                                        href={`#source-${number}`}
                                                        className="mr-2 underline underline-offset-4"
                                                    >
                                                        {t(
                                                            'occupations.show.source_ref',
                                                            {
                                                                number: formatNumber(
                                                                    number,
                                                                ),
                                                            },
                                                        )}
                                                    </a>
                                                ))}
                                            </p>
                                        )}
                                        <p className="flex flex-wrap gap-x-4 text-sm">
                                            <Link
                                                href={field.suggestUrl}
                                                className="underline underline-offset-4"
                                            >
                                                {t('contribute.suggest.button')}
                                                <span className="sr-only">
                                                    {`: ${field.label}`}
                                                </span>
                                            </Link>
                                            <Link
                                                href={field.reportUrl}
                                                className="underline underline-offset-4"
                                            >
                                                {t('contribute.suggest.report')}
                                                <span className="sr-only">
                                                    {`: ${field.label}`}
                                                </span>
                                            </Link>
                                            <Link
                                                href={field.discussUrl}
                                                className="underline underline-offset-4"
                                            >
                                                {t('occupations.show.discuss')}
                                                <span className="sr-only">
                                                    {`: ${field.label}`}
                                                </span>
                                            </Link>
                                        </p>
                                    </dd>
                                </div>
                            ))}
                        </dl>
                    </section>
                ))}

                <section
                    aria-labelledby="sources"
                    className="flex flex-col gap-3"
                >
                    <h2 id="sources" className="text-xl font-semibold">
                        {t('occupations.show.sources_heading')}
                    </h2>
                    {occupation.sources.length === 0 ? (
                        <p className="text-muted-foreground">
                            {t('occupations.show.no_sources')}
                        </p>
                    ) : (
                        <ol className="flex list-decimal flex-col gap-2 pl-6 text-sm">
                            {occupation.sources.map((source) => (
                                <li
                                    key={source.number}
                                    id={`source-${source.number}`}
                                >
                                    <a
                                        href={source.url}
                                        rel="nofollow noopener"
                                        className="underline underline-offset-4"
                                    >
                                        {source.title ?? source.url}
                                    </a>
                                    {source.publisher !== null && (
                                        <>, {source.publisher}</>
                                    )}
                                    {source.license !== null && (
                                        <> ({source.license})</>
                                    )}
                                    {', '}
                                    {t('occupations.show.accessed', {
                                        date: formatDate(source.accessedOn, {
                                            dateStyle: 'medium',
                                        }),
                                    })}
                                    {source.archiveUrl !== null && (
                                        <>
                                            {' · '}
                                            <a
                                                href={source.archiveUrl}
                                                rel="nofollow noopener"
                                                className="underline underline-offset-4"
                                            >
                                                {t('occupations.show.archived')}
                                            </a>
                                        </>
                                    )}
                                </li>
                            ))}
                        </ol>
                    )}
                </section>

                <section
                    aria-labelledby="report"
                    className="flex flex-col gap-2 rounded-md border p-4"
                >
                    <h2 id="report" className="text-lg font-semibold">
                        {t('occupations.show.report_heading')}
                    </h2>
                    <p className="text-sm">
                        {t('occupations.show.report_body')}
                    </p>
                    <div>
                        <Link
                            href={occupation.reportUrl}
                            className="inline-block rounded-md border px-3 py-1.5 text-sm hover:bg-muted"
                        >
                            {t('occupations.show.report_button')}
                        </Link>
                        <Link
                            href={occupation.talkUrl}
                            data-test="talk-link"
                            className="ml-2 inline-block rounded-md border px-3 py-1.5 text-sm hover:bg-muted"
                        >
                            {t('occupations.show.talk_button', {
                                count: formatNumber(occupation.talkCount),
                            })}
                        </Link>
                        <Link
                            href={occupation.compareUrl}
                            data-test="compare-link"
                            className="ml-2 inline-block rounded-md border px-3 py-1.5 text-sm hover:bg-muted"
                        >
                            {t('occupations.show.compare_button')}
                        </Link>
                    </div>
                </section>

                <section
                    aria-labelledby="credits"
                    className="flex flex-col gap-2"
                >
                    <h2 id="credits" className="text-lg font-semibold">
                        {t('contribute.credits.heading')}
                    </h2>
                    {occupation.credits.people.length === 0 &&
                    occupation.credits.anonymous === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            {t('contribute.credits.none')}
                        </p>
                    ) : (
                        <ul className="flex flex-col gap-1 text-sm">
                            {occupation.credits.people.map((person) => (
                                <li key={person.url}>
                                    <Link
                                        href={person.url}
                                        className="underline underline-offset-4"
                                    >
                                        {person.name}
                                    </Link>{' '}
                                    <span className="text-muted-foreground">
                                        (
                                        {t('contribute.credits.contributions', {
                                            count: formatNumber(
                                                person.contributions,
                                            ),
                                        })}
                                        )
                                    </span>
                                </li>
                            ))}
                            {occupation.credits.anonymous > 0 && (
                                <li className="text-muted-foreground">
                                    {t('contribute.credits.anonymous', {
                                        count: formatNumber(
                                            occupation.credits.anonymous,
                                        ),
                                    })}
                                </li>
                            )}
                        </ul>
                    )}
                </section>

                <footer className="flex flex-col gap-1 border-t pt-4 text-sm text-muted-foreground">
                    <p>{t('occupations.show.attribution')}</p>
                    <p>
                        <a
                            href={CONTENT_LICENSE_URL}
                            rel="license"
                            className="underline underline-offset-4"
                        >
                            {t('occupations.show.license')}
                        </a>
                    </p>
                    {occupation.changedAt !== null && (
                        <p>
                            {t('occupations.show.changed_at', {
                                date: formatDate(occupation.changedAt, {
                                    dateStyle: 'medium',
                                }),
                            })}
                        </p>
                    )}
                </footer>
            </article>
        </>
    );
}
