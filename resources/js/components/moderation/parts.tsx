import { Link, usePage } from '@inertiajs/react';
import FieldValue from '@/components/occupations/field-value';
import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type {
    DiffLine,
    Edit,
    Flag,
    ModerationField,
    Person,
} from '@/types/moderation';
import type { DisplayValue, FieldStatus } from '@/types/occupations';
import type { TranslationKey } from '@/types/translation-keys.generated';

// Shared pieces of the moderation pages (PLAN M18).

export const controlClass =
    'w-full rounded-md border border-input bg-background px-3 py-2 text-base shadow-xs focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none md:text-sm';

export function ModerationTabs({
    active,
}: {
    active: 'review' | 'patrol' | 'talk';
}) {
    const { t, localizePath } = useTranslation();
    const tabs = [
        { key: 'review', href: localizePath('/review') },
        { key: 'patrol', href: localizePath('/patrol') },
        { key: 'talk', href: localizePath('/patrol/talk') },
    ] as const;

    return (
        <nav className="flex gap-4 border-b text-sm">
            {tabs.map((tab) => (
                <Link
                    key={tab.key}
                    href={tab.href}
                    aria-current={tab.key === active ? 'page' : undefined}
                    className={cn(
                        '-mb-px border-b-2 px-1 py-2',
                        tab.key === active
                            ? 'border-foreground font-medium'
                            : 'border-transparent text-muted-foreground',
                    )}
                >
                    {t(`moderation.tabs.${tab.key}`)}
                </Link>
            ))}
        </nav>
    );
}

export function Notice() {
    const { t } = useTranslation();
    const { notice } = usePage().props;

    if (typeof notice !== 'string') {
        return null;
    }

    return (
        <p
            role="status"
            className="rounded-md border border-emerald-300 bg-emerald-50 p-3 text-sm text-emerald-900 dark:border-emerald-700 dark:bg-emerald-950 dark:text-emerald-100"
        >
            {t(notice as TranslationKey)}
        </p>
    );
}

export function ShowValue({
    field,
    value,
    status,
}: {
    field: ModerationField;
    value: DisplayValue;
    status: FieldStatus;
}) {
    return (
        <FieldValue
            field={{
                key: field.key,
                label: field.label,
                help: null,
                status,
                withheld: false,
                value,
                valueLocale: null,
                sources: [],
                suggestUrl: '',
                reportUrl: '',
                discussUrl: '',
            }}
        />
    );
}

export function Diff({ lines }: { lines: DiffLine[] }) {
    const { t } = useTranslation();

    return (
        <div>
            <h3 className="mb-1 text-sm font-medium">
                {t('moderation.patrol.diff')}
            </h3>
            <pre className="overflow-x-auto rounded-md border text-sm leading-6 whitespace-pre-wrap">
                {lines.map((line, index) => (
                    <div
                        key={index}
                        data-op={line.op}
                        className={cn(
                            'px-2',
                            line.op === '+' &&
                                'bg-emerald-50 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-100',
                            line.op === '-' &&
                                'bg-red-50 text-red-900 line-through dark:bg-red-950 dark:text-red-100',
                        )}
                    >
                        <span aria-hidden="true" className="select-none">
                            {line.op}{' '}
                        </span>
                        {line.line}
                    </div>
                ))}
            </pre>
        </div>
    );
}

export function PersonName({ person }: { person: Person | null }) {
    const { t } = useTranslation();

    if (person === null) {
        return <span>{t('moderation.patrol.by_ai')}</span>;
    }

    return (
        <span>
            {person.name}
            {person.newAccount && (
                <span className="ml-2 rounded bg-amber-100 px-1.5 py-0.5 text-xs text-amber-900 dark:bg-amber-900 dark:text-amber-100">
                    {t('moderation.patrol.new_account')}
                </span>
            )}
            {person.blocked && (
                <span className="ml-2 rounded bg-red-100 px-1.5 py-0.5 text-xs text-red-900 dark:bg-red-900 dark:text-red-100">
                    {t('moderation.patrol.blocked')}
                </span>
            )}
        </span>
    );
}

export function Flags({ flags }: { flags: Flag[] }) {
    const { t } = useTranslation();

    if (flags.length === 0) {
        return null;
    }

    return (
        <ul className="flex flex-wrap gap-2" data-test="flags">
            {flags.map((flag) => (
                <li
                    key={flag.reason}
                    title={flag.detail ?? undefined}
                    className="rounded bg-amber-100 px-2 py-0.5 text-xs text-amber-900 dark:bg-amber-900 dark:text-amber-100"
                >
                    {t(`moderation.flags.${flag.reason}`)}
                    {flag.detail !== null && `: ${flag.detail}`}
                </li>
            ))}
        </ul>
    );
}

/** What a person wrote around a change: comment, declared interest and sources. */
export function EditNotes({ edit }: { edit: Edit }) {
    const { t } = useTranslation();

    return (
        <dl className="flex flex-col gap-2 text-sm">
            {edit.comment !== null && (
                <div>
                    <dt className="font-medium">
                        {t('moderation.patrol.comment')}
                    </dt>
                    <dd className="whitespace-pre-wrap">{edit.comment}</dd>
                </div>
            )}
            {edit.declaredInterest !== null && (
                <div>
                    <dt className="font-medium">
                        {t('moderation.patrol.interest')}
                    </dt>
                    <dd className="whitespace-pre-wrap">
                        {edit.declaredInterest}
                    </dd>
                </div>
            )}
            {edit.sources.length > 0 && (
                <div>
                    <dt className="font-medium">
                        {t('moderation.patrol.sources')}
                    </dt>
                    <dd>
                        <ul className="list-disc pl-5">
                            {edit.sources.map((source) => (
                                <li key={source.url} className="break-all">
                                    <a
                                        href={source.url}
                                        rel="nofollow noopener noreferrer"
                                        target="_blank"
                                        className="underline underline-offset-4"
                                    >
                                        {source.title ?? source.url}
                                    </a>
                                </li>
                            ))}
                        </ul>
                    </dd>
                </div>
            )}
        </dl>
    );
}
