import { Head } from '@inertiajs/react';
import { useTranslation } from '@/lib/i18n';

// Placeholder home page until the public occupation pages arrive (M13).
export default function Welcome() {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('home.subtitle')} />
            <section className="mx-auto flex w-full max-w-5xl flex-col gap-3 px-4 py-16">
                <h1 className="text-3xl font-semibold">{t('home.title')}</h1>
                <p className="text-xl">{t('home.subtitle')}</p>
                <p className="max-w-prose text-muted-foreground">
                    {t('home.description')}
                </p>
            </section>
        </>
    );
}
