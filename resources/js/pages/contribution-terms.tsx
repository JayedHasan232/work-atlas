import { Head } from '@inertiajs/react';
import { useTranslation } from '@/lib/i18n';

type Props = {
    version: string;
};

const sections = [
    'license',
    'credit',
    'own_work',
    'private',
    'respect',
] as const;

// The terms people accept at signup. The text lives in lang/*/terms.php; its version is
// atlas.contribution_terms_version.
export default function ContributionTerms({ version }: Props) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('terms.title')} />
            <article className="mx-auto flex w-full max-w-prose flex-col gap-6 px-4 py-12">
                <header className="flex flex-col gap-3">
                    <h1 className="text-3xl font-semibold">
                        {t('terms.title')}
                    </h1>
                    <p
                        role="note"
                        className="rounded-md border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100"
                    >
                        {t('terms.draft_notice')}
                    </p>
                    <p>{t('terms.intro')}</p>
                </header>

                {sections.map((section) => (
                    <section key={section} className="flex flex-col gap-2">
                        <h2 className="text-xl font-semibold">
                            {t(`terms.sections.${section}.title`)}
                        </h2>
                        <p>{t(`terms.sections.${section}.body`)}</p>
                    </section>
                ))}

                <footer className="flex flex-col gap-1 text-sm text-muted-foreground">
                    <a
                        className="underline underline-offset-4"
                        href="https://creativecommons.org/licenses/by-sa/4.0/"
                        rel="license"
                    >
                        {t('terms.license_link')}
                    </a>
                    <p>{t('terms.version', { version })}</p>
                </footer>
            </article>
        </>
    );
}
