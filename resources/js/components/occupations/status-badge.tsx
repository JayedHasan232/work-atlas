import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { FieldStatus } from '@/types/occupations';

const styles: Record<FieldStatus, string> = {
    ai_draft:
        'border-amber-300 bg-amber-50 text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100',
    sourced:
        'border-sky-300 bg-sky-50 text-sky-900 dark:border-sky-700 dark:bg-sky-950 dark:text-sky-100',
    community_supported:
        'border-teal-300 bg-teal-50 text-teal-900 dark:border-teal-700 dark:bg-teal-950 dark:text-teal-100',
    verified:
        'border-green-300 bg-green-50 text-green-900 dark:border-green-700 dark:bg-green-950 dark:text-green-100',
    disputed:
        'border-red-300 bg-red-50 text-red-900 dark:border-red-700 dark:bg-red-950 dark:text-red-100',
};

/** How far a value has been checked (rule 3), as a small labelled badge. */
export default function StatusBadge({ status }: { status: FieldStatus }) {
    const { t } = useTranslation();

    return (
        <span
            className={cn(
                'inline-flex shrink-0 items-center rounded-full border px-2 py-0.5 text-xs font-medium',
                styles[status],
            )}
        >
            <span className="sr-only">{t('occupations.show.status')}: </span>
            {t(`occupations.status.${status}`)}
        </span>
    );
}
