import { Head, Link } from '@inertiajs/react';
import { useTranslation } from '@/lib/i18n';

// Placeholder home page until the public occupation pages arrive (M13).
export default function Welcome() {
    const { t, localizePath } = useTranslation();

    return (
        <>
            <Head title={t('home.subtitle')} />
            <section className="mx-auto flex w-full max-w-5xl flex-col gap-3 px-4 py-16">
                <h1 className="text-3xl font-semibold">{t('home.title')}</h1>
                <p className="text-xl">{t('home.subtitle')}</p>
                <p className="max-w-prose text-muted-foreground">
                    {t('home.description')}
                </p>
                <div className="mt-4 flex flex-wrap gap-3">
                    <Link
                        href={localizePath('/guide')}
                        data-test="guide-link"
                        className="inline-flex min-h-11 items-center rounded-md bg-primary px-4 py-2 font-medium text-primary-foreground hover:bg-primary/90"
                    >
                        {t('home.guide_link')}
                    </Link>
                    <Link
                        href={localizePath('/search')}
                        className="inline-flex min-h-11 items-center rounded-md border px-4 py-2 font-medium hover:bg-muted"
                    >
                        {t('home.search_link')}
                    </Link>
                </div>
            </section>
        </>
    );
}
