import { Head, Link } from '@inertiajs/react';
import { useTranslation } from '@/lib/i18n';
import type { PolicyLink } from '@/lib/policies';

type Props = {
    policies: PolicyLink[];
};

// `/policies` (PLAN M23): every policy with a one-line summary, and the contribution terms.
export default function Policies({ policies }: Props) {
    const { t, localizePath } = useTranslation();
    const linkClass = 'font-medium underline underline-offset-4';

    return (
        <>
            <Head title={t('policies.title')} />
            <article className="mx-auto flex w-full max-w-prose flex-col gap-6 px-4 py-12">
                <header className="flex flex-col gap-3">
                    <h1 className="text-3xl font-semibold">
                        {t('policies.title')}
                    </h1>
                    <p>{t('policies.intro')}</p>
                </header>

                <ul className="flex flex-col gap-4">
                    {policies.map((policy) => (
                        <li key={policy.slug} className="flex flex-col gap-1">
                            <Link
                                href={localizePath(`/policies/${policy.slug}`)}
                                className={linkClass}
                            >
                                {t(`policies.${policy.key}.title`)}
                            </Link>
                            <p className="text-sm text-muted-foreground">
                                {t(`policies.${policy.key}.summary`)}
                            </p>
                        </li>
                    ))}
                    <li className="flex flex-col gap-1">
                        <Link
                            href={localizePath('/contribution-terms')}
                            className={linkClass}
                        >
                            {t('terms.title')}
                        </Link>
                        <p className="text-sm text-muted-foreground">
                            {t('policies.terms_summary')}
                        </p>
                    </li>
                </ul>

                <p className="text-sm text-muted-foreground">
                    {t('policies.contact_pending')}
                </p>
            </article>
        </>
    );
}
