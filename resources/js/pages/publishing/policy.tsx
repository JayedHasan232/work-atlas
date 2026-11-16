import { Head, Link } from '@inertiajs/react';
import { useTranslation } from '@/lib/i18n';
import { policySections } from '@/lib/policies';
import type { PolicyLink, PolicySectionKey } from '@/lib/policies';

type Props = {
    policy: PolicyLink;
    policies: PolicyLink[];
};

// One policy page (PLAN M23). Every text is a draft until the maintainer approves it.
export default function Policy({ policy, policies }: Props) {
    const { t, localizePath } = useTranslation();
    const sections: readonly PolicySectionKey[] = policySections[policy.key];

    return (
        <>
            <Head title={t(`policies.${policy.key}.title`)} />
            <article className="mx-auto flex w-full max-w-prose flex-col gap-6 px-4 py-12">
                <header className="flex flex-col gap-3">
                    <h1 className="text-3xl font-semibold">
                        {t(`policies.${policy.key}.title`)}
                    </h1>
                    <p
                        role="note"
                        className="rounded-md border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100"
                    >
                        {t('policies.draft_notice')}
                    </p>
                    <p>{t(`policies.${policy.key}.summary`)}</p>
                </header>

                {sections.map((section) => (
                    <section key={section} className="flex flex-col gap-2">
                        <h2 className="text-xl font-semibold">
                            {t(`${section}.title`)}
                        </h2>
                        <p>{t(`${section}.body`)}</p>
                    </section>
                ))}

                <footer className="flex flex-col gap-3 border-t pt-6 text-sm text-muted-foreground">
                    <p>{t('policies.contact_pending')}</p>
                    <nav aria-label={t('policies.all_policies')}>
                        <ul className="flex flex-wrap gap-x-4 gap-y-1">
                            {policies
                                .filter((other) => other.slug !== policy.slug)
                                .map((other) => (
                                    <li key={other.slug}>
                                        <Link
                                            href={localizePath(
                                                `/policies/${other.slug}`,
                                            )}
                                            className="underline underline-offset-4"
                                        >
                                            {t(`policies.${other.key}.title`)}
                                        </Link>
                                    </li>
                                ))}
                            <li>
                                <Link
                                    href={localizePath('/policies')}
                                    className="underline underline-offset-4"
                                >
                                    {t('policies.all_policies')}
                                </Link>
                            </li>
                        </ul>
                    </nav>
                </footer>
            </article>
        </>
    );
}
