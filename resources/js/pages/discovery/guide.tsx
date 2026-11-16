import { Head, Link, router } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useEffect, useState } from 'react';
import ValueText from '@/components/discovery/value-text';
import StatusBadge from '@/components/occupations/status-badge';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { Guide as GuideProps, GuideMatch } from '@/types/discovery';

declare global {
    interface Window {
        umami?: { track: (event: string) => void };
    }
}

// The guided filter (PLAN M20): short questions, then the work that matches a person's situation,
// each with why and its limits. The answers live only in the URL; at most an anonymous "completed"
// event goes to analytics, and only where the site's own analytics runs.
export default function Guide({ guide }: { guide: GuideProps }) {
    const { t, formatNumber } = useTranslation();
    const [answers, setAnswers] = useState<Record<string, string>>(
        guide.answers,
    );
    const [editing, setEditing] = useState(guide.results === null);
    const [chosen, setChosen] = useState<string[]>([]);
    const answered = Object.keys(answers).length;

    useEffect(() => {
        if (guide.results !== null) {
            window.umami?.track('guide_completed');
        }
    }, [guide.results]);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        router.get(guide.guideUrl, answers, {
            onSuccess: () => setEditing(false),
        });
    };

    const answer = (question: string, option: string | null) => {
        const next = { ...answers };

        if (option === null) {
            delete next[question];
        } else {
            next[question] = option;
        }

        setAnswers(next);
    };

    const choose = (slug: string) =>
        setChosen(
            chosen.includes(slug)
                ? chosen.filter((item) => item !== slug)
                : [...chosen, slug].slice(0, 3),
        );

    const compareHref =
        guide.compareUrl +
        '?' +
        chosen.map((slug) => `o[]=${encodeURIComponent(slug)}`).join('&');

    return (
        <>
            <Head title={t('guide.title')} />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 px-4 py-8">
                <header className="flex flex-col gap-2">
                    <h1 className="text-2xl font-semibold">
                        {t('guide.title')}
                    </h1>
                    <p className="text-muted-foreground">{t('guide.intro')}</p>
                </header>

                {editing ? (
                    <form onSubmit={submit} className="flex flex-col gap-4">
                        {guide.questions.map((question) => (
                            <fieldset
                                key={question.key}
                                className="flex flex-col gap-2 rounded-lg border p-4"
                                data-test={`question-${question.key}`}
                            >
                                <legend className="px-1 font-medium">
                                    {question.title}
                                </legend>
                                <p className="text-sm text-muted-foreground">
                                    {question.help}
                                </p>
                                <div className="flex flex-wrap gap-2">
                                    {question.options.map((option) => {
                                        const checked =
                                            answers[question.key] ===
                                            option.key;

                                        return (
                                            <label
                                                key={option.key}
                                                className={cn(
                                                    'flex min-h-11 cursor-pointer items-center rounded-md border px-3 py-2 text-sm has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-ring',
                                                    checked &&
                                                        'border-foreground bg-muted font-medium',
                                                )}
                                                data-test={`answer-${question.key}-${option.key}`}
                                            >
                                                <input
                                                    type="radio"
                                                    className="sr-only"
                                                    name={question.key}
                                                    value={option.key}
                                                    checked={checked}
                                                    onChange={() =>
                                                        answer(
                                                            question.key,
                                                            option.key,
                                                        )
                                                    }
                                                    onClick={() =>
                                                        checked &&
                                                        answer(
                                                            question.key,
                                                            null,
                                                        )
                                                    }
                                                />
                                                {option.label}
                                            </label>
                                        );
                                    })}
                                </div>
                            </fieldset>
                        ))}
                        <div className="sticky bottom-0 flex flex-wrap items-center gap-3 border-t bg-background py-3">
                            <Button
                                type="submit"
                                data-test="guide-submit"
                                disabled={answered === 0}
                            >
                                {t('guide.submit')}
                            </Button>
                            <span className="text-sm text-muted-foreground">
                                {t('guide.progress', {
                                    answered: formatNumber(answered),
                                    total: formatNumber(guide.questions.length),
                                })}
                            </span>
                        </div>
                    </form>
                ) : (
                    <div className="flex flex-wrap gap-3">
                        <Button
                            variant="outline"
                            data-test="guide-change"
                            onClick={() => setEditing(true)}
                        >
                            {t('guide.change')}
                        </Button>
                        <Link
                            href={guide.guideUrl}
                            className="self-center text-sm underline underline-offset-4"
                        >
                            {t('guide.start_over')}
                        </Link>
                    </div>
                )}

                {!editing && guide.results !== null && (
                    <section
                        className="flex flex-col gap-4"
                        aria-labelledby="results"
                    >
                        <h2 id="results" className="text-xl font-semibold">
                            {t('guide.results.title')}
                        </h2>
                        <p data-test="results-count">
                            {guide.results.conditions === 0
                                ? t('guide.results.no_conditions')
                                : guide.results.total === 0
                                  ? t('guide.results.none')
                                  : t('guide.results.count', {
                                        total: formatNumber(
                                            guide.results.total,
                                        ),
                                        checked: formatNumber(
                                            guide.results.checked,
                                        ),
                                    })}
                        </p>

                        {guide.results.matches.length > 0 && (
                            <div className="sticky top-0 z-10 flex flex-wrap items-center gap-3 border-b bg-background py-2 text-sm">
                                {chosen.length >= 2 ? (
                                    <Link
                                        href={compareHref}
                                        data-test="compare-selected"
                                        className="rounded-md border px-3 py-1.5 font-medium hover:bg-muted"
                                    >
                                        {t('guide.results.compare_selected', {
                                            count: formatNumber(chosen.length),
                                        })}
                                    </Link>
                                ) : (
                                    <span className="text-muted-foreground">
                                        {t('guide.results.compare_hint')}
                                    </span>
                                )}
                            </div>
                        )}

                        <ol className="flex flex-col gap-3">
                            {guide.results.matches.map((match) => (
                                <MatchCard
                                    key={match.occupation.slug}
                                    match={match}
                                    chosen={chosen.includes(
                                        match.occupation.slug,
                                    )}
                                    full={chosen.length >= 3}
                                    onChoose={() =>
                                        choose(match.occupation.slug)
                                    }
                                />
                            ))}
                        </ol>

                        <aside className="flex flex-col gap-2 rounded-lg border bg-muted/40 p-4 text-sm">
                            <h3 className="font-medium">
                                {t('guide.results.about')}
                            </h3>
                            <ul className="list-disc ps-5">
                                {guide.results.limits.map((limit) => (
                                    <li key={limit}>{limit}</li>
                                ))}
                            </ul>
                        </aside>
                    </section>
                )}
            </div>
        </>
    );
}

function MatchCard({
    match,
    chosen,
    full,
    onChoose,
}: {
    match: GuideMatch;
    chosen: boolean;
    full: boolean;
    onChoose: () => void;
}) {
    const { t } = useTranslation();

    return (
        <li
            className="flex flex-col gap-3 rounded-lg border p-4"
            data-test="guide-match"
        >
            <div className="flex flex-wrap items-start justify-between gap-2">
                <Link
                    href={match.occupation.url}
                    lang={match.occupation.nameLocale}
                    className="text-lg font-medium underline underline-offset-4"
                >
                    {match.occupation.name}
                </Link>
                <label className="flex min-h-11 items-center gap-2 text-sm">
                    <input
                        type="checkbox"
                        checked={chosen}
                        disabled={!chosen && full}
                        onChange={onChoose}
                        data-test={`compare-choose-${match.occupation.slug}`}
                    />
                    {t('guide.results.compare')}
                    <span className="sr-only">{`: ${match.occupation.name}`}</span>
                </label>
            </div>

            {match.reasons.length > 0 && (
                <div className="flex flex-col gap-1">
                    <h3 className="text-sm font-medium">
                        {t('guide.results.why')}
                    </h3>
                    <ul className="flex flex-col gap-2 text-sm">
                        {match.reasons.map((reason) => (
                            <li
                                key={reason.question}
                                className="flex flex-col gap-0.5"
                                data-test="guide-reason"
                            >
                                <span>
                                    <span className="font-medium">
                                        {t(
                                            `guide.results.outcomes.${reason.outcome}`,
                                        )}
                                        :
                                    </span>{' '}
                                    {reason.field}:{' '}
                                    <ValueText value={reason.value} />
                                </span>
                                <span className="flex flex-wrap items-center gap-2 text-muted-foreground">
                                    {t('guide.results.you_said', {
                                        answer: reason.answer,
                                    })}
                                    {reason.status !== null && (
                                        <StatusBadge status={reason.status} />
                                    )}
                                </span>
                            </li>
                        ))}
                    </ul>
                </div>
            )}

            {match.limits.length > 0 && (
                <div className="flex flex-col gap-1">
                    <h3 className="text-sm font-medium">
                        {t('guide.results.limits')}
                    </h3>
                    <ul
                        className="list-disc ps-5 text-sm text-muted-foreground"
                        data-test="guide-limits"
                    >
                        {match.limits.map((limit) => (
                            <li key={limit}>{limit}</li>
                        ))}
                    </ul>
                </div>
            )}
        </li>
    );
}
