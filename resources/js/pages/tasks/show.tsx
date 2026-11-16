import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import FieldValue from '@/components/occupations/field-value';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/lib/i18n';
import type { TranslationKey } from '@/types/translation-keys.generated';
import type {
    DisplayValue,
    FieldStatus,
    OccupationLink,
} from '@/types/occupations';

type Answer = 'agree' | 'disagree' | 'skip';

type Props = {
    task: {
        id: number;
        occupation: OccupationLink;
        field: { key: string; label: string; help: string | null };
        value: DisplayValue;
        status: FieldStatus;
        answerUrl: string;
        suggestUrl: string;
    } | null;
    answered: number;
    agreementsNeeded: number;
};

// Micro-task verification (PLAN M17): one yes/no question at a time, made to be answered on a phone in
// a few seconds. "Not sure" skips the question for this person only.
export default function MicroTask({ task, answered, agreementsNeeded }: Props) {
    const { t, formatNumber, localizePath } = useTranslation();
    const { notice } = usePage().props;
    const [sending, setSending] = useState(false);

    const answer = (choice: Answer) => {
        if (task === null) {
            return;
        }

        router.post(
            task.answerUrl,
            { answer: choice },
            {
                onStart: () => setSending(true),
                onFinish: () => setSending(false),
            },
        );
    };

    return (
        <>
            <Head title={t('tasks.title')} />
            <div className="mx-auto flex w-full max-w-xl flex-col gap-6 px-4 py-8">
                <header className="flex flex-col gap-2">
                    <h1 className="text-2xl font-semibold">
                        {t('tasks.title')}
                    </h1>
                    <p className="text-muted-foreground">{t('tasks.intro')}</p>
                </header>

                {typeof notice === 'string' && (
                    <p
                        role="status"
                        className="rounded-md border border-emerald-300 bg-emerald-50 p-3 text-sm text-emerald-900 dark:border-emerald-700 dark:bg-emerald-950 dark:text-emerald-100"
                    >
                        {t(notice as TranslationKey)}
                    </p>
                )}

                {task === null ? (
                    <section className="flex flex-col gap-3 rounded-lg border p-5">
                        <p className="text-lg">{t('tasks.done')}</p>
                        <Link
                            href={localizePath('/occupations')}
                            className="underline underline-offset-4"
                        >
                            {t('tasks.browse')}
                        </Link>
                    </section>
                ) : (
                    <section
                        aria-labelledby="question"
                        className="flex flex-col gap-4 rounded-lg border p-5"
                        data-test="micro-task"
                    >
                        <div className="flex flex-col gap-1">
                            <p className="text-sm text-muted-foreground">
                                {t('tasks.occupation')}
                            </p>
                            <p
                                className="text-lg font-medium"
                                lang={task.occupation.nameLocale}
                            >
                                {task.occupation.name}
                            </p>
                        </div>
                        <div className="flex flex-col gap-1">
                            <p className="font-medium">{task.field.label}</p>
                            {task.field.help !== null && (
                                <p className="text-sm text-muted-foreground">
                                    {task.field.help}
                                </p>
                            )}
                        </div>
                        <div className="rounded-md bg-muted p-3 text-lg">
                            <FieldValue
                                field={{
                                    key: task.field.key,
                                    label: task.field.label,
                                    help: null,
                                    status: task.status,
                                    withheld: false,
                                    value: task.value,
                                    valueLocale: null,
                                    sources: [],
                                    suggestUrl: task.suggestUrl,
                                    reportUrl: '',
                                    discussUrl: '',
                                }}
                            />
                        </div>
                        <p className="text-sm text-muted-foreground">
                            {t('tasks.draft_note')}
                        </p>
                        <h2 id="question" className="text-xl font-semibold">
                            {t('tasks.question')}
                        </h2>
                        <div className="grid gap-3 sm:grid-cols-2">
                            <Button
                                type="button"
                                size="lg"
                                className="h-12 text-base"
                                disabled={sending}
                                onClick={() => answer('agree')}
                                data-test="answer-agree"
                            >
                                {t('tasks.yes')}
                            </Button>
                            <Button
                                type="button"
                                size="lg"
                                variant="outline"
                                className="h-12 text-base"
                                disabled={sending}
                                onClick={() => answer('disagree')}
                                data-test="answer-disagree"
                            >
                                {t('tasks.no')}
                            </Button>
                        </div>
                        <Button
                            type="button"
                            variant="ghost"
                            className="h-12 text-base"
                            disabled={sending}
                            onClick={() => answer('skip')}
                            data-test="answer-skip"
                        >
                            {t('tasks.skip')}
                        </Button>
                        <p className="text-sm text-muted-foreground">
                            {t('tasks.no_hint')}{' '}
                            <Link
                                href={task.suggestUrl}
                                className="underline underline-offset-4"
                            >
                                {t('tasks.suggest')}
                            </Link>
                        </p>
                    </section>
                )}

                <footer className="flex flex-col gap-1 text-sm text-muted-foreground">
                    <p>
                        {t('tasks.how', {
                            count: formatNumber(agreementsNeeded),
                        })}
                    </p>
                    <p>
                        {t('tasks.answered', {
                            count: formatNumber(answered),
                        })}
                    </p>
                </footer>
            </div>
        </>
    );
}
