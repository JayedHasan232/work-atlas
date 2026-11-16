import { Link } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import LanguageSwitcher from '@/components/language-switcher';
import { useTranslation } from '@/lib/i18n';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const { t, localizePath } = useTranslation();

    return (
        <div className="flex min-h-svh flex-col bg-background">
            <header className="flex justify-end p-4">
                <LanguageSwitcher />
            </header>
            <main className="flex flex-1 flex-col items-center justify-center gap-6 p-6 md:p-10">
                <div className="w-full max-w-sm">
                    <div className="flex flex-col gap-8">
                        <div className="flex flex-col items-center gap-4">
                            <Link
                                href={localizePath(home().url)}
                                className="flex flex-col items-center gap-2 font-medium"
                            >
                                <div className="mb-1 flex h-9 w-9 items-center justify-center rounded-md">
                                    <AppLogoIcon
                                        aria-hidden="true"
                                        className="size-9 fill-current text-[var(--foreground)] dark:text-white"
                                    />
                                </div>
                                <span className="sr-only">
                                    {t('common.nav.home')}
                                </span>
                            </Link>

                            <div className="space-y-2 text-center">
                                {title && (
                                    <h1 className="text-xl font-medium">
                                        {t(title)}
                                    </h1>
                                )}
                                {description && (
                                    <p className="text-center text-sm text-muted-foreground">
                                        {t(description)}
                                    </p>
                                )}
                            </div>
                        </div>
                        {children}
                    </div>
                </div>
            </main>
        </div>
    );
}
