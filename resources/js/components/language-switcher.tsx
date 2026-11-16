import { Link, usePage } from '@inertiajs/react';
import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import { update } from '@/routes/locale';

/**
 * Lists the supported languages. On public pages, which have one URL per language, each entry links
 * to the same page in that language. Other pages (sign-in, settings) have one URL for both, so the
 * entry saves the choice in the session and reloads the page.
 */
export default function LanguageSwitcher({
    className,
}: {
    className?: string;
}) {
    const { localization } = usePage().props;
    const { t } = useTranslation();
    const itemClass = 'rounded-md px-2 py-1 text-sm leading-normal';

    return (
        <nav aria-label={t('common.language.label')} className={className}>
            <ul className="flex items-center gap-1">
                {localization.locales.map((option) => (
                    <li key={option.code}>
                        {option.code === localization.locale ? (
                            <span
                                lang={option.code}
                                aria-current="true"
                                className={cn(itemClass, 'font-medium')}
                            >
                                {option.name}
                            </span>
                        ) : option.url !== null ? (
                            <Link
                                href={option.url}
                                lang={option.code}
                                hrefLang={option.code}
                                className={cn(itemClass, 'hover:bg-accent')}
                            >
                                {option.name}
                            </Link>
                        ) : (
                            <Link
                                href={update()}
                                data={{ locale: option.code }}
                                as="button"
                                preserveScroll
                                lang={option.code}
                                className={cn(
                                    itemClass,
                                    'cursor-pointer hover:bg-accent',
                                )}
                            >
                                {option.name}
                            </Link>
                        )}
                    </li>
                ))}
            </ul>
        </nav>
    );
}
